# CampusList API Dokümantasyonu

## Genel Bilgiler

**Base URL:** `https://app.listcampus.com/api/v1`

**Authentication:** Bearer Token (Tüm endpoint'ler için gerekli)

**Content-Type:** `application/json`

**Accept:** `application/json`

---

## Authentication

Tüm API endpoint'leri Bearer Token ile korunmaktadır. İsteklerinizde `Authorization` header'ında token'ınızı göndermelisiniz:

```
Authorization: Bearer {your_token}
```

---

## Universities Endpoints

### 1. Üniversite Listesi

Üniversiteleri filtreleme, sıralama ve sayfalama ile listeler.

**Endpoint:** `GET /api/v1/universities`

**Query Parametreleri:**

| Parametre | Tip | Açıklama | Örnek |
|-----------|-----|----------|-------|
| `search` | string | İsim, kısa isim veya lokasyona göre arama | `MIT` |
| `location` | string | Lokasyon filtresi | `Boston` |
| `region_code` | string | Bölge kodu (ISO 3166-1 alpha-2) | `US`, `TR` |
| `administrative_area` | string | İl/eyalet filtresi | `Massachusetts` |
| `locality` | string | Şehir filtresi | `Cambridge` |
| `type` | string | Üniversite tipi | `Public`, `Private` |
| `acceptance_rate_min` | integer | Minimum kabul oranı (0-100) | `50` |
| `acceptance_rate_max` | integer | Maksimum kabul oranı (0-100) | `80` |
| `enrollment_min` | integer | Minimum toplam öğrenci sayısı | `5000` |
| `enrollment_max` | integer | Maksimum toplam öğrenci sayısı | `20000` |
| `enrollment_undergraduate_min` | integer | Minimum lisans öğrenci sayısı | `3000` |
| `enrollment_graduate_min` | integer | Minimum lisansüstü öğrenci sayısı | `1000` |
| `tuition_min` | integer | Minimum yıllık ücret | `20000` |
| `tuition_max` | integer | Maksimum yıllık ücret | `60000` |
| `tuition_currency` | string | Para birimi (3 harf) | `USD`, `EUR` |
| `gpa_min` | float | Minimum GPA gereksinimi | `3.0` |
| `sat_max` | integer | Maksimum SAT skoru | `1500` |
| `act_max` | integer | Maksimum ACT skoru | `35` |
| `majors` | string/array | Major ID'leri (virgülle ayrılmış) | `1,2,3` veya `[1,2,3]` |
| `notable_majors` | string/array | Öne çıkan major ID'leri | `5,6` |
| `founded_min` | integer | Minimum kuruluş yılı | `1800` |
| `founded_max` | integer | Maksimum kuruluş yılı | `2000` |
| `sort_by` | string | Sıralama alanı | `name`, `founded`, `acceptance_rate`, `enrollment_total`, `tuition_undergraduate`, `requirement_gpa_min`, `requirement_sat`, `requirement_act` |
| `sort_order` | string | Sıralama yönü | `asc`, `desc` |
| `per_page` | integer | Sayfa başına kayıt (max: 100, default: 15) | `20` |
| `page` | integer | Sayfa numarası | `1` |

**Örnek İstek:**

```bash
curl -X GET "https://app.listcampus.com/api/v1/universities?search=MIT&tuition_min=30000&tuition_max=60000&majors=1,2,3&per_page=20" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

**Örnek Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Massachusetts Institute of Technology",
      "slug": "massachusetts-institute-of-technology",
      "short_name": "MIT",
      "location": "Cambridge, MA, USA",
      "region_code": "US",
      "administrative_area": "Massachusetts",
      "locality": "Cambridge",
      "website": "https://web.mit.edu",
      "founded": "1861-04-10",
      "founded_year": "1861",
      "type": "Private",
      "meta_description": "Find everything you need to know about Massachusetts Institute of Technology...",
      "overview": "MIT is a world-renowned institution...",
      "google_maps_uri": "https://maps.google.com/...",
      "address": "77 Massachusetts Ave, Cambridge, MA 02139",
      "phone": "+1-617-253-1000",
      "gps_coordinates": {
        "latitude": 42.3601,
        "longitude": -71.0942
      },
      "acceptance_rate": 7,
      "ranking": {
        "national": 2,
        "global": 1
      },
      "enrollment": {
        "total": 11520,
        "undergraduate": 4561,
        "graduate": 6959,
        "raw": {
          "total": 11520,
          "undergraduate": 4561,
          "graduate": 6959
        }
      },
      "tuition": {
        "undergraduate": 53790,
        "graduate": 53790,
        "international": 53790,
        "currency": "USD",
        "raw": {
          "undergraduate": 53790,
          "graduate": 53790,
          "intl": 53790,
          "currency": "USD"
        }
      },
      "requirements": {
        "gpa_min": 4.0,
        "sat": 1520,
        "act": 35,
        "toefl": 90,
        "ielts": 7.0,
        "raw": {
          "gpa_min": 4.0,
          "sat": 1520,
          "act": 35,
          "toefl": 90,
          "ielts": 7.0
        }
      },
      "deadlines": {
        "fall": "2025-01-01",
        "spring": "2025-11-01"
      },
      "majors": [
        {
          "id": 1,
          "name": "Computer Science",
          "slug": "computer-science",
          "is_notable": true
        },
        {
          "id": 2,
          "name": "Engineering",
          "slug": "engineering",
          "is_notable": true
        }
      ],
      "notable_majors": [
        {
          "id": 1,
          "name": "Computer Science",
          "slug": "computer-science"
        }
      ],
      "majors_raw": ["Computer Science", "Engineering"],
      "notable_majors_raw": ["Computer Science"],
      "scholarships": ["Merit-based", "Need-based"],
      "housing": {
        "on_campus": 15000,
        "off_campus": 18000
      },
      "campus_life": {
        "clubs": ["Robotics Club", "Hack Club"],
        "sports": ["Basketball", "Soccer"]
      },
      "contact": {
        "address": "77 Massachusetts Ave, Cambridge, MA 02139",
        "email": "admissions@mit.edu",
        "phone": "+1-617-253-1000"
      },
      "faq": [
        {
          "question": "What is the application deadline?",
          "answer": "The application deadline for Fall 2025 is January 1, 2025."
        }
      ],
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100,
    "from": 1,
    "to": 20
  }
}
```

---

### 2. Üniversite Detayı (ID ile)

Belirli bir üniversitenin detaylı bilgilerini getirir.

**Endpoint:** `GET /api/v1/universities/{id}`

**Path Parametreleri:**

| Parametre | Tip | Açıklama |
|-----------|-----|----------|
| `id` | integer | Üniversite ID'si |

**Örnek İstek:**

```bash
curl -X GET "https://app.listcampus.com/api/v1/universities/1" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

**Response:** Yukarıdaki ile aynı format (tek bir üniversite objesi)

---

### 3. Üniversite Detayı (Slug ile)

Slug ile üniversite detaylarını getirir. SEO-friendly URL'ler için kullanılır.

**Endpoint:** `GET /api/v1/universities/slug/{slug}`

**Path Parametreleri:**

| Parametre | Tip | Açıklama |
|-----------|-----|----------|
| `slug` | string | Üniversite slug'ı |

**Örnek İstek:**

```bash
curl -X GET "https://app.listcampus.com/api/v1/universities/slug/massachusetts-institute-of-technology" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

**Response:** Yukarıdaki ile aynı format (tek bir üniversite objesi)

---

## Media Endpoints

### 1. Media Listesi

Tüm medya dosyalarını filtreleme ve sayfalama ile listeler.

**Endpoint:** `GET /api/v1/media`

**Query Parametreleri:**

| Parametre | Tip | Açıklama | Örnek |
|-----------|-----|----------|-------|
| `university_id` | integer | Üniversite ID'sine göre filtreleme | `1` |
| `university_slug` | string | Üniversite slug'ına göre filtreleme | `massachusetts-institute-of-technology` |
| `disk` | string | Disk adına göre filtreleme | `r2` |
| `mime_type` | string | MIME type'a göre filtreleme (tam veya tip) | `image/jpeg`, `image`, `video` |
| `search` | string | Dosya adı veya orijinal adına göre arama | `photo` |
| `sort_by` | string | Sıralama alanı | `created_at`, `updated_at`, `size`, `filename`, `mime_type` |
| `sort_order` | string | Sıralama yönü | `asc`, `desc` |
| `per_page` | integer | Sayfa başına kayıt (max: 100, default: 15) | `20` |
| `page` | integer | Sayfa numarası | `1` |

**Örnek İstek:**

```bash
curl -X GET "https://app.listcampus.com/api/v1/media?university_id=1&mime_type=image&sort_by=created_at&sort_order=desc" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

**Örnek Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "disk": "r2",
      "filename": "mit-photo-abc123",
      "original_name": "MIT Campus Photo",
      "extension": "jpg",
      "mime_type": "image/jpeg",
      "size": 2456789,
      "size_human": "2.34 MB",
      "path": "universities/massachusetts-institute-of-technology/photos/mit-photo-abc123.jpg",
      "url": "https://your-r2-domain.com/universities/massachusetts-institute-of-technology/photos/mit-photo-abc123.jpg",
      "glide_urls": {
        "thumbnail": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg?w=150&h=150&fit=crop&q=85",
        "small": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg?w=400&h=400&fit=contain&q=85",
        "medium": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg?w=800&h=800&fit=contain&q=85",
        "large": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg?w=1600&h=1600&fit=contain&q=90",
        "original": "https://your-r2-domain.com/universities/massachusetts-institute-of-technology/photos/mit-photo-abc123.jpg",
        "custom": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg"
      },
      "directory": "universities/massachusetts-institute-of-technology/photos",
      "meta": {
        "google_photo_name": "places/ChIJ.../photos/AWn5SU6...",
        "university_id": 1,
        "place_id": "ChIJqSw3Qk9kZIgRUwjsDcF0vEA",
        "width_px": 3992,
        "height_px": 2245,
        "slug": "massachusetts-institute-of-technology"
      },
      "university": {
        "id": 1,
        "name": "Massachusetts Institute of Technology",
        "slug": "massachusetts-institute-of-technology"
      },
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45,
    "from": 1,
    "to": 15
  }
}
```

---

### 2. Media Detayı

Belirli bir medya dosyasının detaylarını getirir.

**Endpoint:** `GET /api/v1/media/{id}`

**Path Parametreleri:**

| Parametre | Tip | Açıklama |
|-----------|-----|----------|
| `id` | integer | Media ID'si |

**Örnek İstek:**

```bash
curl -X GET "https://app.listcampus.com/api/v1/media/1" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

**Response:** Yukarıdaki ile aynı format (tek bir media objesi)

---

### 3. Üniversite Medyaları

Belirli bir üniversiteye ait tüm medya dosyalarını getirir.

**Endpoint:** `GET /api/v1/universities/{universityId}/media`

**Path Parametreleri:**

| Parametre | Tip | Açıklama |
|-----------|-----|----------|
| `universityId` | integer | Üniversite ID'si |

**Örnek İstek:**

```bash
curl -X GET "https://app.listcampus.com/api/v1/universities/1/media" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

**Örnek Response:**

```json
{
  "success": true,
  "data": {
    "university": {
      "id": 1,
      "name": "Massachusetts Institute of Technology",
      "slug": "massachusetts-institute-of-technology"
    },
    "media": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "disk": "r2",
        "filename": "mit-photo-abc123",
        "original_name": "MIT Campus Photo",
        "extension": "jpg",
        "mime_type": "image/jpeg",
        "size": 2456789,
        "size_human": "2.34 MB",
        "path": "universities/massachusetts-institute-of-technology/photos/mit-photo-abc123.jpg",
        "url": "https://your-r2-domain.com/universities/massachusetts-institute-of-technology/photos/mit-photo-abc123.jpg",
        "glide_urls": {
          "thumbnail": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg?w=150&h=150&fit=crop&q=85",
          "small": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg?w=400&h=400&fit=contain&q=85",
          "medium": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg?w=800&h=800&fit=contain&q=85",
          "large": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg?w=1600&h=1600&fit=contain&q=90",
          "original": "https://your-r2-domain.com/universities/massachusetts-institute-of-technology/photos/mit-photo-abc123.jpg",
          "custom": "https://app.listcampus.com/glide/universities%2Fmassachusetts-institute-of-technology%2Fphotos%2Fmit-photo-abc123.jpg"
        },
        "directory": "universities/massachusetts-institute-of-technology/photos",
        "meta": {
          "google_photo_name": "places/ChIJ.../photos/AWn5SU6...",
          "university_id": 1,
          "place_id": "ChIJqSw3Qk9kZIgRUwjsDcF0vEA",
          "width_px": 3992,
          "height_px": 2245
        },
        "university": {
          "id": 1,
          "name": "Massachusetts Institute of Technology",
          "slug": "massachusetts-institute-of-technology"
        },
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-01-01T00:00:00.000000Z"
      }
    ],
    "count": 5
  }
}
```

---

## Görsel Optimizasyonu (Glide)

Tüm görseller Glide ile optimize edilebilir. Media response'larında `glide_urls` objesi içinde hazır boyutlar ve özel URL'ler bulunur.

### Glide URL Formatı

```
GET /glide/{path}?w={width}&h={height}&fit={fit}&q={quality}&fm={format}
```

### Glide Parametreleri

| Parametre | Tip | Açıklama | Örnek Değer | Varsayılan |
|-----------|-----|----------|-------------|------------|
| `w` | integer | Genişlik (piksel) | `800` | - |
| `h` | integer | Yükseklik (piksel) | `600` | - |
| `fit` | string | Sığdırma modu | `contain`, `max`, `fill`, `stretch`, `crop` | `contain` |
| `q` | integer | Kalite (0-100) | `90` | `90` |
| `fm` | string | Format | `jpg`, `png`, `webp` | Orijinal format |
| `filt` | string | Filtre | `greyscale`, `sepia` | - |
| `blur` | integer | Blur efekti (0-100) | `10` | - |
| `pixel` | integer | Pixelate efekti | `5` | - |
| `dpr` | float | Device pixel ratio | `2` | `1` |

### Fit Modları

- `contain` - Görseli belirtilen boyutlara sığdırır, oranı korur
- `max` - Maksimum boyutlara sığdırır, oranı korur
- `fill` - Belirtilen boyutlara doldurur, oranı korur (boşluklar beyaz)
- `stretch` - Belirtilen boyutlara gerer, oranı korumaz
- `crop` - Belirtilen boyutlara kırpar, oranı korur

### Hazır Boyutlar

Media response'larında `glide_urls` objesi içinde şu hazır boyutlar bulunur:

- `thumbnail` - 150x150, crop, %85 kalite
- `small` - 400x400, contain, %85 kalite
- `medium` - 800x800, contain, %85 kalite
- `large` - 1600x1600, contain, %90 kalite
- `original` - Orijinal dosya URL'i
- `custom` - Özel parametreler için base URL

### Örnek Kullanımlar

#### Hazır Boyut Kullanımı
```html
<!-- Thumbnail -->
<img src="https://app.listcampus.com/glide/universities%2Fmit%2Fphotos%2Fphoto.jpg?w=150&h=150&fit=crop&q=85" />

<!-- Medium -->
<img src="https://app.listcampus.com/glide/universities%2Fmit%2Fphotos%2Fphoto.jpg?w=800&h=800&fit=contain&q=85" />
```

#### Özel Boyut ve Format
```html
<!-- WebP formatında, 1200px genişlik -->
<img src="https://app.listcampus.com/glide/universities%2Fmit%2Fphotos%2Fphoto.jpg?w=1200&fm=webp&q=90" />

<!-- Greyscale filtresi -->
<img src="https://app.listcampus.com/glide/universities%2Fmit%2Fphotos%2Fphoto.jpg?w=800&filt=greyscale" />
```

#### Responsive Image (srcset)
```html
<img 
  src="https://app.listcampus.com/glide/universities%2Fmit%2Fphotos%2Fphoto.jpg?w=400&q=85"
  srcset="
    https://app.listcampus.com/glide/universities%2Fmit%2Fphotos%2Fphoto.jpg?w=400&q=85 400w,
    https://app.listcampus.com/glide/universities%2Fmit%2Fphotos%2Fphoto.jpg?w=800&q=85 800w,
    https://app.listcampus.com/glide/universities%2Fmit%2Fphotos%2Fphoto.jpg?w=1600&q=90 1600w
  "
  sizes="(max-width: 400px) 400px, (max-width: 800px) 800px, 1600px"
  alt="University Photo"
/>
```

### Performans Notları

- Glide görselleri otomatik olarak cache'ler
- İlk istekte görsel işlenir ve cache'lenir
- Sonraki istekler cache'den servis edilir
- Cache `storage/app/glide-cache` dizininde saklanır
- Production'da cache temizleme stratejisi uygulanmalıdır

---

## Majors Endpoints

### 1. Major Listesi

Tüm major'ları listeler.

**Endpoint:** `GET /api/v1/majors`

**Query Parametreleri:**

| Parametre | Tip | Açıklama | Örnek |
|-----------|-----|----------|-------|
| `search` | string | İsme göre arama | `Computer` |
| `min_universities` | integer | Minimum üniversite sayısı | `10` |
| `sort_by` | string | Sıralama alanı | `name`, `universities_count` |
| `sort_order` | string | Sıralama yönü | `asc`, `desc` |
| `per_page` | integer | Sayfa başına kayıt (max: 100, default: 50) | `50` |
| `page` | integer | Sayfa numarası | `1` |

**Örnek İstek:**

```bash
curl -X GET "https://app.listcampus.com/api/v1/majors?search=Computer&min_universities=10&sort_by=universities_count&sort_order=desc" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

**Örnek Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Computer Science",
      "slug": "computer-science",
      "universities_count": 150,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Engineering",
      "slug": "engineering",
      "universities_count": 200,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 50,
    "total": 150,
    "from": 1,
    "to": 50
  }
}
```

---

### 2. Major Detayı

Belirli bir major'ın detaylarını ve bu major'ı sunan üniversiteleri getirir.

**Endpoint:** `GET /api/v1/majors/{id}`

**Path Parametreleri:**

| Parametre | Tip | Açıklama |
|-----------|-----|----------|
| `id` | integer | Major ID'si |

**Örnek İstek:**

```bash
curl -X GET "https://app.listcampus.com/api/v1/majors/1" \
  -H "Authorization: Bearer {your_token}" \
  -H "Accept: application/json"
```

**Örnek Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Computer Science",
    "slug": "computer-science",
    "universities_count": 150,
    "universities": {
      "data": [
        {
          "id": 1,
          "name": "Massachusetts Institute of Technology",
          "slug": "massachusetts-institute-of-technology",
          "location": "Cambridge, MA, USA"
        },
        {
          "id": 2,
          "name": "Stanford University",
          "slug": "stanford-university",
          "location": "Stanford, CA, USA"
        }
      ],
      "meta": {
        "current_page": 1,
        "last_page": 8,
        "per_page": 20,
        "total": 150
      }
    },
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

---

## Tüm Filtreleme Seçenekleri

### 🔍 Arama ve Genel Filtreler

| Parametre | Tip | Açıklama | Örnek Değer | Operatör |
|-----------|-----|----------|-------------|----------|
| `search` | string | İsim, kısa isim veya lokasyona göre genel arama (LIKE) | `MIT`, `Harvard` | `LIKE %value%` |
| `location` | string | Lokasyon filtresi (LIKE) | `Boston`, `Cambridge` | `LIKE %value%` |
| `type` | string | Üniversite tipi (tam eşleşme) | `Public`, `Private` | `=` |

### 🌍 Lokasyon Filtreleri

| Parametre | Tip | Açıklama | Örnek Değer | Operatör |
|-----------|-----|----------|-------------|----------|
| `region_code` | string | Bölge kodu - ISO 3166-1 alpha-2 (tam eşleşme) | `US`, `TR`, `GB`, `CA` | `=` |
| `administrative_area` | string | İl/eyalet filtresi (LIKE) | `Massachusetts`, `California`, `İstanbul` | `LIKE %value%` |
| `locality` | string | Şehir filtresi (LIKE) | `Boston`, `Cambridge`, `New York` | `LIKE %value%` |

### 📊 Kabul Oranı Filtreleri

| Parametre | Tip | Açıklama | Örnek Değer | Operatör | Aralık |
|-----------|-----|----------|-------------|----------|--------|
| `acceptance_rate_min` | integer | Minimum kabul oranı | `50` | `>=` | 0-100 |
| `acceptance_rate_max` | integer | Maksimum kabul oranı | `80` | `<=` | 0-100 |

**Not:** Her iki parametre birlikte kullanılarak aralık belirlenebilir: `acceptance_rate_min=50&acceptance_rate_max=80`

### 👥 Öğrenci Sayısı (Enrollment) Filtreleri

| Parametre | Tip | Açıklama | Örnek Değer | Operatör |
|-----------|-----|----------|-------------|----------|
| `enrollment_min` | integer | Minimum toplam öğrenci sayısı | `5000` | `>=` |
| `enrollment_max` | integer | Maksimum toplam öğrenci sayısı | `20000` | `<=` |
| `enrollment_undergraduate_min` | integer | Minimum lisans öğrenci sayısı | `3000` | `>=` |
| `enrollment_graduate_min` | integer | Minimum lisansüstü öğrenci sayısı | `1000` | `>=` |

**Not:** 
- `enrollment_min` ve `enrollment_max` birlikte kullanılabilir
- `enrollment_undergraduate_min` ve `enrollment_graduate_min` sadece minimum değer alır

### 💰 Ücret (Tuition) Filtreleri

| Parametre | Tip | Açıklama | Örnek Değer | Operatör |
|-----------|-----|----------|-------------|----------|
| `tuition_min` | integer | Minimum yıllık lisans ücreti | `20000` | `>=` |
| `tuition_max` | integer | Maksimum yıllık lisans ücreti | `60000` | `<=` |
| `tuition_currency` | string | Para birimi (3 harf ISO kodu, tam eşleşme) | `USD`, `EUR`, `GBP`, `TRY` | `=` |

**Not:**
- `tuition_min` ve `tuition_max` birlikte kullanılarak aralık belirlenebilir
- `tuition_currency` ile para birimi filtrelenebilir
- Ücret değerleri lisans (undergraduate) ücretlerine göre filtrelenir

### 📚 Akademik Gereksinimler (Requirements) Filtreleri

| Parametre | Tip | Açıklama | Örnek Değer | Operatör | Açıklama |
|-----------|-----|----------|-------------|----------|----------|
| `gpa_min` | float | Minimum GPA gereksinimi (bu GPA'ya sahip öğrenciler için uygun üniversiteler) | `3.0`, `3.5` | `<=` | Üniversitenin minimum GPA gereksinimi bu değerden küçük veya eşit olmalı |
| `sat_max` | integer | Maksimum SAT skoru (bu SAT skoruna sahip öğrenciler için uygun üniversiteler) | `1400`, `1500` | `<=` | Üniversitenin minimum SAT gereksinimi bu değerden küçük veya eşit olmalı |
| `act_max` | integer | Maksimum ACT skoru (bu ACT skoruna sahip öğrenciler için uygun üniversiteler) | `30`, `35` | `<=` | Üniversitenin minimum ACT gereksinimi bu değerden küçük veya eşit olmalı |

**Not:** 
- Bu filtreler "bu skorlara sahip öğrenciler için uygun üniversiteler" mantığıyla çalışır
- Örneğin `gpa_min=3.5` ile 3.5 GPA'ya sahip bir öğrenci için uygun üniversiteleri bulursunuz
- Üniversitenin minimum gereksinimi belirtilen değerden küçük veya eşit olmalı

### 🎓 Major Filtreleri

| Parametre | Tip | Açıklama | Örnek Değer | Format | Operatör |
|-----------|-----|----------|-------------|--------|----------|
| `majors` | string/array | Major ID'leri - Bu major'ları sunan üniversiteler | `1,2,3` veya `[1,2,3]` | Virgülle ayrılmış string veya array | `IN` (many-to-many) |
| `notable_majors` | string/array | Öne çıkan major ID'leri - Bu major'ları öne çıkan olarak sunan üniversiteler | `5,6` veya `[5,6]` | Virgülle ayrılmış string veya array | `IN` (many-to-many) |

**Not:**
- `majors` parametresi ile belirtilen major'lardan **en az birini** sunan üniversiteler gelir
- `notable_majors` parametresi ile belirtilen major'lardan **en az birini** öne çıkan olarak sunan üniversiteler gelir
- Her iki parametre birlikte kullanılabilir
- Major ID'lerini öğrenmek için `/api/v1/majors` endpoint'ini kullanın

### 📅 Kuruluş Yılı (Founded) Filtreleri

| Parametre | Tip | Açıklama | Örnek Değer | Operatör |
|-----------|-----|----------|-------------|----------|
| `founded_min` | integer | Minimum kuruluş yılı | `1800`, `1900` | `>=` (yıl) |
| `founded_max` | integer | Maksimum kuruluş yılı | `2000`, `2020` | `<=` (yıl) |

**Not:** Her iki parametre birlikte kullanılarak yıl aralığı belirlenebilir

### 🔄 Sıralama (Sorting) Parametreleri

| Parametre | Tip | Açıklama | İzin Verilen Değerler | Varsayılan |
|-----------|-----|----------|----------------------|------------|
| `sort_by` | string | Sıralama alanı | `name`, `founded`, `acceptance_rate`, `enrollment_total`, `tuition_undergraduate`, `requirement_gpa_min`, `requirement_sat`, `requirement_act` | `name` |
| `sort_order` | string | Sıralama yönü | `asc`, `desc` | `asc` |

**Sıralama Alanları:**
- `name` - Üniversite adına göre alfabetik
- `founded` - Kuruluş yılına göre
- `acceptance_rate` - Kabul oranına göre
- `enrollment_total` - Toplam öğrenci sayısına göre
- `tuition_undergraduate` - Lisans ücretine göre
- `requirement_gpa_min` - Minimum GPA gereksinimine göre
- `requirement_sat` - SAT gereksinimine göre
- `requirement_act` - ACT gereksinimine göre

### 📄 Sayfalama (Pagination) Parametreleri

| Parametre | Tip | Açıklama | Varsayılan | Maksimum |
|-----------|-----|----------|------------|----------|
| `per_page` | integer | Sayfa başına kayıt sayısı | `15` | `100` |
| `page` | integer | Sayfa numarası | `1` | - |

**Not:** `per_page` parametresi maksimum 100 değerini alabilir. Daha yüksek değerler otomatik olarak 100'e sınırlanır.

---

## Filtreleme Örnekleri

### Örnek 1: Fiyat Aralığına Göre Filtreleme

```bash
GET /api/v1/universities?tuition_min=20000&tuition_max=50000&tuition_currency=USD&sort_by=tuition_undergraduate&sort_order=asc
```

**Açıklama:** USD cinsinden 20.000-50.000 arası ücretli üniversiteleri ücrete göre artan sırada listeler.

### Örnek 2: Major'a Göre Filtreleme

```bash
GET /api/v1/universities?majors=5&enrollment_min=5000&acceptance_rate_max=50
```

**Açıklama:** ID'si 5 olan major'ı sunan, minimum 5000 öğrenciye sahip ve maksimum %50 kabul oranına sahip üniversiteleri listeler.

### Örnek 3: Lokasyon ve Requirements'a Göre Filtreleme

```bash
GET /api/v1/universities?region_code=US&locality=Boston&gpa_min=3.0&sat_max=1500
```

**Açıklama:** ABD'de Boston şehrinde bulunan, minimum GPA gereksinimi 3.0 veya daha düşük ve minimum SAT gereksinimi 1500 veya daha düşük üniversiteleri listeler.

### Örnek 4: Çoklu Filtreleme

```bash
GET /api/v1/universities?type=Private&tuition_min=30000&majors=1,2,3&enrollment_min=10000&sort_by=acceptance_rate&sort_order=asc&per_page=25
```

**Açıklama:** 
- Özel üniversiteler
- Minimum 30.000 USD ücretli
- 1, 2 veya 3 ID'li major'lardan en az birini sunan
- Minimum 10.000 öğrenciye sahip
- Kabul oranına göre artan sırada
- Sayfa başına 25 kayıt

### Örnek 5: Kuruluş Yılı ve Öğrenci Sayısı

```bash
GET /api/v1/universities?founded_min=1800&founded_max=1950&enrollment_min=10000&enrollment_max=50000
```

**Açıklama:** 1800-1950 yılları arasında kurulmuş ve 10.000-50.000 arası öğrenciye sahip üniversiteleri listeler.

### Örnek 6: Öne Çıkan Major'lara Göre Filtreleme

```bash
GET /api/v1/universities?notable_majors=1,5&region_code=US&type=Public
```

**Açıklama:** ABD'de bulunan, devlet üniversiteleri ve 1 veya 5 ID'li major'lardan en az birini öne çıkan olarak sunan üniversiteleri listeler.

### Örnek 7: Genel Arama ve Lokasyon

```bash
GET /api/v1/universities?search=Technology&administrative_area=Massachusetts&sort_by=founded&sort_order=desc
```

**Açıklama:** İsmi, kısa ismi veya lokasyonunda "Technology" geçen, Massachusetts eyaletinde bulunan üniversiteleri kuruluş yılına göre azalan sırada listeler.

---

## Hata Yönetimi

### Hata Response Formatı

```json
{
  "success": false,
  "message": "Hata mesajı"
}
```

### HTTP Status Kodları

| Kod | Açıklama |
|-----|----------|
| `200` | Başarılı |
| `401` | Unauthorized - Token geçersiz veya eksik |
| `404` | Not Found - Kayıt bulunamadı |
| `422` | Validation Error - Geçersiz parametreler |
| `500` | Server Error - Sunucu hatası |

### Örnek Hata Response

```json
{
  "success": false,
  "message": "Üniversite bulunamadı."
}
```

---

## Rate Limiting

API kullanımı için rate limiting uygulanmaktadır. Detaylar için lütfen bizimle iletişime geçin.

---

## Notlar

- Tüm tarihler ISO 8601 formatında (`YYYY-MM-DD` veya `YYYY-MM-DDTHH:mm:ssZ`)
- Tüm sayısal değerler integer veya float olarak döner
- JSON array parametreleri hem string (virgülle ayrılmış) hem de array formatında kabul edilir
- Pagination için `meta` objesi her zaman döner
- `raw` alanları JSON backup verilerini içerir (geriye dönük uyumluluk için)

---

## Destek

Sorularınız için: support@campuslist.com

