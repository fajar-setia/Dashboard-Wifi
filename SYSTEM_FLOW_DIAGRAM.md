# System Flow Diagram - Dashboard WiFi

## Alur Data Sistem

```
┌──────────────────────────────┐
│  Perangkat ONU/ONT           │
│  (ZTE F609, F612, dll)       │
└──────────────┬───────────────┘
               │
               │ Managed by
               ▼
┌──────────────────────────────┐
│  CMS API Server              │
│  http://172.16.105.3:3080    │
│                              │
│  /v1/oauth/token             │
│  /v1/ont                     │
│  /v1/ont/{sn}/connect        │
└──────────────┬───────────────┘
               │
               │ HTTP Request + OAuth2
               ▼
┌──────────────────────────────┐
│  OnuApiService               │
│  (Laravel Service)           │
│                              │
│  - Token Caching (50 min)    │
│  - Parallel Requests (20x)   │
└──────────────┬───────────────┘
               │
               │ Used by Controller
               ▼
┌──────────────────────────────┐
│  MariaDB Database            │
│                              │
│  - ONU Mapping (CSV)         │
│  - Google Sheets Integration │
└──────────────┬───────────────┘
               │
               │ Query data
               ▼
┌──────────────────────────────┐
│  DashboardController         │
│  (Laravel Backend)           │
│                              │
│  /dashboard                  │
│  /dashboard/monthly-user-data│
└──────────────┬───────────────┘
               │
               │ Render with Vite
               ▼
┌──────────────────────────────┐
│  Dashboard Frontend          │
│  http://localhost:8001       │
│                              │
│  - Chart.js (Weekly/Monthly) │
│  - Location Charts           │
│  - Connected Users Table     │
└──────────────────────────────┘
```

---

## Penjelasan Detail

### 1. CMS API Server (`172.16.105.3:3080`)
- **Authentication**: OAuth2 Bearer Token
  - POST `/v1/oauth/token` dengan Basic Auth (cms-web:cms-web)
  - Response: `access_token` valid 50 menit
- **Endpoints**:
  - GET `/v1/ont` - List semua ONU/ONT devices
  - GET `/v1/ont/{sn}/connect` - WiFi clients per device
- **Response Format**: JSON dengan structure:
  ```json
  {
    "sn": "ZTEG12345678",
    "wifiClients": {
      "5G": [...],
      "2_4G": [...],
      "unknown": [...]
    }
  }
  ```

### 2. OnuApiService
Service layer untuk komunikasi dengan CMS API:
- **Token Caching**: Cache Bearer token selama 50 menit
- **Parallel Requests**: Batch 20 concurrent requests untuk performa optimal
- **Methods**:
  - `getBearerToken()` - Fetch dan cache OAuth token
  - `getAllOnu()` - Get list semua ONU devices
  - `getAllOnuWithClients()` - Get semua ONU + WiFi clients (parallel)

### 3. ONU Mapping Data (CSV & Google Sheets)

**CSV Source**: `public/storage/ACSfiks.csv`
```csv
No,Nama Lokasi,Kemantren,Nomor Tiket,Type ONU,User Paket,Password Paket,Alamat IP,Serial Number ONU
1,Taman,Perak,TKT001,F609,paket200,pass123,172.16.1.1,ZTEG12345678
```

**Google Sheets Integration**:
- **Paket 110**: Sheet ID `1Wtkfylu-BbdIzvV7ZT_M7rEOg2ANBh5ylvea1sp37m8`
- **Paket 200**: Sheet ID dari config `services.google.sheet_id`
- **Range**: `'paket 110'!B2:I201` atau `'paket 200'!B2:I201`
- **Mapping**: Serial Number → Location + Kemantren
- **Cache**: 10 menit (600 detik)

### 4. Database Tables

**daily_user_stats**:
```sql
date         | user_count | meta (JSON)      | updated_at
-------------|------------|------------------|------------
2026-01-20   | 245        | {"sample": 245}  | 14:30:00
```
- Unique key: `date`
- Menyimpan peak user count per hari

**daily_location_stats**:
```sql
date       | location  | kemantren | sn           | user_count | updated_at
-----------|-----------|-----------|--------------|------------|------------
2026-01-20 | Taman     | Perak     | ZTEG1234... | 45         | 14:30:00
```
- Unique key: `(date, location)`
- Menyimpan peak user per lokasi per hari

### 5. Dashboard Controller
Laravel controller yang handle routes:
- `GET /dashboard` - Main dashboard page (7 days stats)
- `GET /dashboard/monthly-user-data` - JSON data untuk chart 30 hari
- `GET /dashboard/location-clients` - Filter per lokasi

### 6. Dashboard Frontend
- **Rekap Total User Chart**: Line chart dengan toggle Weekly/Monthly
- **Location Charts**: Breakdown per lokasi dengan filter Kemantren
- **Connected Users Table**: Real-time status dari OnuApiService
- **Tech Stack**: Blade templates, TailwindCSS, Chart.js, Vite HMR

---

## File Penting

**CSV Mapping**: `/public/storage/ACSfiks.csv`
**Google Service Account**: `/config/google/service-accounts.json`
**ONU Service**: `/app/Services/OnuApiService.php`
**Dashboard Controller**: `/app/Http/Controllers/DashboardController.php`

---

## Development Servers
- **Vite**: `http://localhost:5174` (HMR)
- **Laravel**: `http://localhost:8001`
