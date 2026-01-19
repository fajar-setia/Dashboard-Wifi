# Single Terminal + Network Access Setup

## 🎯 Option 1: Development (dengan HMR) - Recommended untuk Development

### Install Concurrently
```bash
npm install --save-dev concurrently
```

### Update package.json
Tambahkan script baru di `package.json`:

```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "serve": "php artisan serve --host=0.0.0.0",
    "start": "concurrently \"npm run serve\" \"npm run dev\" --names \"PHP,VITE\" --prefix-colors \"yellow,cyan\""
  }
}
```

### Update vite.config.js
Tambahkan `--host` untuk Vite agar bisa diakses dari network:

```javascript
export default defineConfig({
    server: {
        host: '0.0.0.0',  // ← Tambahkan ini
        port: 5173
    },
    // ... config lainnya
});
```

### Jalankan (1 Terminal!)
```bash
npm run start
```

**Akses dari device lain:**
- Dashboard: `http://192.168.x.x:8000`
- Vite HMR: `http://192.168.x.x:5173`

*(ganti `192.168.x.x` dengan IP komputer Anda)*

---

## 🚀 Option 2: Production Build - Paling Simple!

Kalau tidak butuh hot reload (untuk production/demo):

### Build Assets Sekali
```bash
npm run build
```

### Jalankan PHP Saja (1 Terminal!)
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Keuntungan:**
- ✅ Hanya 1 terminal
- ✅ Assets sudah compiled (lebih cepat)
- ✅ Bisa diakses dari network
- ❌ No hot reload (harus `npm run build` lagi kalau edit frontend)

**Akses:**
- `http://192.168.x.x:8000`

---

## 📋 Cek IP Komputer Anda

```bash
# Linux
ip addr show | grep "inet " | grep -v 127.0.0.1

# Atau
hostname -I
```

---

## 🔧 Production Ready (Bonus)

### Untuk production server gunakan:

**1. Build assets:**
```bash
npm run build
```

**2. Setup systemd service:**

Create `/etc/systemd/system/laravel-dashboard.service`:
```ini
[Unit]
Description=Laravel Dashboard
After=network.target

[Service]
Type=simple
User=vazul
WorkingDirectory=/home/vazul/Downloads/Projekkkk/Dashboard-Wifi
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8000
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

**Enable dan start:**
```bash
sudo systemctl enable laravel-dashboard
sudo systemctl start laravel-dashboard
```

---

## 📊 Perbandingan

| Method | Terminals | HMR | Network Access | Use Case |
|--------|-----------|-----|----------------|----------|
| **Option 1: Concurrently** | 1 | ✅ | ✅ | Development |
| **Option 2: Build Only** | 1 | ❌ | ✅ | Demo/Production |
| **Original (2 terminals)** | 2 | ✅ | ❌ | Local dev only |

## ✅ Recommendation

**Untuk Development**: Gunakan **Option 1** (Concurrently)
**Untuk Demo/Production**: Gunakan **Option 2** (Build Only)
