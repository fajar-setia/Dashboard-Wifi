# Fitur: Daily MAC Address Logging

## Deskripsi
Sistem sekarang mencatat setiap MAC address WiFi yang terhubung sepanjang hari ke dalam tabel `daily_mac_logs`. Ini memastikan bahwa rekap total user tidak akan menjadi 0 bahkan jika terjadi error API saat pergantian hari.

## Cara Kerja

### 1. Saat Command `collect:daily-users` Dijalankan
- Mengambil data real-time dari API ONU
- Menyimpan setiap MAC address yang terhubung ke tabel `daily_mac_logs`
- Update `last_seen` timestamp jika MAC address sudah ada sebelumnya
- Menghitung total unique MAC dari tabel `daily_mac_logs` untuk hari itu
- Menyimpan nilai tertinggi (peak) ke tabel `daily_user_stats`

### 2. Tabel `daily_mac_logs`
```sql
Columns:
- id: Primary key
- date: Tanggal (INDEX)
- mac_address: MAC address device (INDEX)
- location: Lokasi terhubung
- kemantren: Kemantren
- first_seen: Kapan MAC pertama kali dilihat hari ini
- last_seen: Update terakhir
- created_at, updated_at

Unique constraint: (date, mac_address)
```

### 3. Rekap Total User
Total user untuk hari tertentu = COUNT(DISTINCT mac_address) dari tabel `daily_mac_logs` untuk tanggal tersebut.

Ini lebih akurat karena:
- Accumulative sepanjang hari (tidak hanya snapshot saat command dijalankan)
- Menyimpan riwayat setiap MAC yang pernah terhubung
- Jika API error, data yang sudah tercatat tetap aman

## Query untuk Melihat Data

### Melihat semua MAC address yang terhubung hari ini:
```php
DB::table('daily_mac_logs')
    ->where('date', now()->toDateString())
    ->get();
```

### Melihat summary per lokasi:
```php
DB::table('daily_mac_logs')
    ->where('date', now()->toDateString())
    ->groupBy('location')
    ->select('location', DB::raw('COUNT(DISTINCT mac_address) as total_users'))
    ->get();
```

### Melihat data daily_user_stats (dengan info dari MAC log):
```php
DB::table('daily_user_stats')
    ->where('date', now()->toDateString())
    ->get();
```

## Keuntungan
1. **Tidak ada data 0 saat error**: Meskipun API error saat pergantian hari, data dari MAC log yang sudah terkumpul tetap digunakan.
2. **Akumulatif**: Semakin lama hari berlangsung, semakin banyak MAC yang tercatat (peak terus meningkat atau tetap).
3. **Historis**: Dapat melihat semua MAC yang pernah terhubung dalam sehari.
4. **Lokasi tracking**: Tahu di mana setiap user terhubung.

## Pembersihan Data (Optional)
Jika ingin menghapus log MAC lama (misalnya lebih dari 30 hari):
```php
DB::table('daily_mac_logs')
    ->where('date', '<', now()->subDays(30)->toDateString())
    ->delete();
```
