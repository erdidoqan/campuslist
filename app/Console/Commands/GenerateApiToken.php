<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate-token 
                            {--user= : Kullanıcı email veya ID}
                            {--name= : Token adı (opsiyonel)}
                            {--abilities=* : Token yetkileri (varsayılan: ["*"])}
                            {--expires-at= : Token sona erme tarihi (YYYY-MM-DD formatında, opsiyonel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kullanıcı için API token oluşturur';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->option('user');

        // Eğer user belirtilmediyse varsayılan API user'ı kullan
        if (empty($userIdentifier)) {
            $user = $this->getDefaultApiUser();

            if (! $user) {
                $this->error('Varsayılan API kullanıcısı bulunamadı. Lütfen önce seed çalıştırın: php artisan db:seed');
                $this->line('Veya --user parametresi ile bir kullanıcı belirtin.');

                return self::FAILURE;
            }

            $this->info(sprintf('Varsayılan API kullanıcısı kullanılıyor: %s (ID: %d)', $user->email, $user->id));
        } else {
            // Kullanıcıyı bul
            $user = $this->findUser($userIdentifier);

            if (! $user) {
                $this->error(sprintf('Kullanıcı bulunamadı: %s', $userIdentifier));

                return self::FAILURE;
            }

            $this->info(sprintf('Kullanıcı bulundu: %s (ID: %d)', $user->email, $user->id));
        }

        // Token adı
        $tokenName = $this->option('name') ?? $this->ask('Token adı', 'API Token '.now()->format('Y-m-d H:i:s'));

        // Token yetkileri
        $abilities = $this->option('abilities');
        if (empty($abilities)) {
            $abilities = ['*'];
        }

        // Token sona erme tarihi
        $expiresAt = null;
        if ($this->option('expires-at')) {
            try {
                $expiresAt = \Carbon\Carbon::parse($this->option('expires-at'));
            } catch (\Exception $e) {
                $this->warn('Geçersiz tarih formatı, token süresiz olacak.');
            }
        }

        // Token oluştur
        $token = $user->createToken($tokenName, $abilities);
        
        // Expires_at ayarla (varsa)
        if ($expiresAt) {
            $token->accessToken->expires_at = $expiresAt;
            $token->accessToken->save();
        }

        // Sonuçları göster
        $this->newLine();
        $this->info('✅ Token başarıyla oluşturuldu!');
        $this->newLine();

        $this->table(
            ['Özellik', 'Değer'],
            [
                ['Token ID', $token->accessToken->id],
                ['Token Adı', $token->accessToken->name],
                ['Kullanıcı', $user->name.' ('.$user->email.')'],
                ['Yetkiler', implode(', ', $token->accessToken->abilities)],
                ['Oluşturulma', $token->accessToken->created_at->format('Y-m-d H:i:s')],
                ['Son Geçerlilik', $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : 'Süresiz'],
            ]
        );

        $this->newLine();
        $this->line('🔑 <fg=green>Token:</>');
        $this->line('<fg=yellow>'.$token->plainTextToken.'</>');
        $this->newLine();

        $this->warn('⚠️  Bu token\'ı güvenli bir yerde saklayın. Bir daha gösterilmeyecek!');
        $this->newLine();

        // Kullanım örneği göster
        $this->line('📝 <fg=cyan>Kullanım Örneği:</>');
        $this->line('curl -X GET http://localhost:8000/api/v1/universities \\');
        $this->line('  -H "Authorization: Bearer '.$token->plainTextToken.'" \\');
        $this->line('  -H "Accept: application/json"');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Get default API user
     *
     * @return User|null
     */
    protected function getDefaultApiUser(): ?User
    {
        return User::where('email', 'api@campuslist.com')->first();
    }

    /**
     * Find user by email or ID
     *
     * @param  string|int  $identifier
     * @return User|null
     */
    protected function findUser($identifier): ?User
    {
        // ID ile ara
        if (is_numeric($identifier)) {
            return User::find((int) $identifier);
        }

        // Email ile ara
        return User::where('email', $identifier)->first();
    }
}
