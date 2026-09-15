# Campus Lost & Found

Backend **Laravel 13** (MySQL) + frontend **Vue 3 SPA** (Vite + Tailwind). Sistem untuk melaporkan, mencari, mencocokkan, dan mengklaim barang hilang & ditemukan di kampus.

## Fitur

- Lapor barang hilang / ditemukan, lengkap dengan foto & pertanyaan verifikasi
- Pencarian + filter (status, tipe, kategori, lokasi) + sorting
- Possible match otomatis (skor kategori/lokasi/waktu/teks, ambang 50)
- Klaim barang → verifikasi jawaban (server-side, jawaban benar tidak pernah dikirim ke klien) → admin approve/reject
- Notifikasi (possible match, klaim baru, klaim disetujui/ditolak, barang di-return)
- Dashboard user & admin (statistik + grafik)
- Moderasi laporan oleh admin (approve/reject/suspend/delete)
- CSRF protect (session-based) untuk seluruh API

## Persyaratan

- PHP 8.3+ (`curl`, `openssl`, `mbstring`, `pdo_mysql`, `gd`, `fileinfo`, `intl`, `zip`)
- Composer 2+
- MySQL 8
- Node.js 18+ (untuk frontend)

## Setup

```bash
# 1. Backend dependencies + environment
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link

# 2. Database
# buat database MySQL bernama campus_lost_found, lalu:
php artisan migrate --seed

# 3. Frontend
cd frontend
npm install
npm run build
cp dist/* ../public/        # pindahkan build SPA ke public Laravel
cd ..
```

Akun demo (dari seeder):

| Email | Password | Role |
|-------|----------|------|
| admin@campus.ac.id | `password` | admin |
| demo@campus.ac.id | `password` | student |

## Menjalankan (dev)

```bash
php artisan serve                # terminal 1 — backend http://localhost:8000
cd frontend && npm run dev       # terminal 2 — SPA http://localhost:5173 (proxy /api & /storage ke 8000)
```

Postingan baru mulai `pending` dan muncul untuk publik setelah **approve** oleh admin
(Admin → Report Moderation).

## Produksi

1. `cd frontend && npm install && npm run build`
2. Salin `frontend/dist/*` ke `public/` (rata ke root `public/index.html`)
3. Pastikan `php artisan storage:link` jalan (foto diakses via `/storage/...`)

## Struktur

- `app/Http/Controllers/` — Auth, Item, Claim, Notification, Dashboard, Admin
- `app/Services/MatchingService.php` — logika pencocokan possible match
- `app/Support/ApiResponse.php` — format respons `{success, data}` / `{success, message}`
- `routes/api.php` — seluruh endpoint API (session + CSRF via middleware `web`)
- `database/migrations` + `database/seeders` — skema & data awal
- `frontend/` — SPA Vue 3 (terpisah, build ke `frontend/dist`)