# Laravel-2110040-20261 — Backend API Minimarket

REST API untuk aplikasi mobile **minimarket** (React Native + Expo).
Dibangun dengan **Laravel 13** + **Laravel Sanctum** (API Token) dan database **MySQL**.

Repo aplikasi mobile: https://github.com/Jri1810/mobile2_2110040_20261

- **Praktikum:** Modul 11 — Instalasi Laravel 13, Sanctum API Token, dan Integrasi `fetch()` Login & Register
- **Mata kuliah:** Aplikasi Mobile 2
- **NIM:** 2110040

---

## Persyaratan

| Perangkat | Versi |
|---|---|
| PHP | ≥ 8.3 |
| Composer | 2.x |
| MySQL / MariaDB | 5.7+ / 10.x (Laragon atau XAMPP) |

Cek versi PHP:

```bash
php -v
```

---

## Cara Menjalankan

### 1. Clone repository

```bash
git clone https://github.com/Jri1810/Laravel-2110040-20261.git
cd Laravel-2110040-20261
```

### 2. Install dependency

```bash
composer install
```

### 3. Buat database

Buat database kosong bernama **`db_minimarket_app`** melalui HeidiSQL, phpMyAdmin,
atau terminal MySQL:

```sql
CREATE DATABASE db_minimarket_app;
```

### 4. Siapkan file `.env`

```bash
cp .env.example .env
```

Buka `.env`, sesuaikan bagian database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_minimarket_app
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Generate application key

```bash
php artisan key:generate
```

### 6. Jalankan migrasi

Perintah ini membuat tabel `users` (dengan kolom `no_hp` dan `foto`),
`personal_access_tokens` (Sanctum), serta tabel pendukung lainnya.

```bash
php artisan migrate
```

### 7. Nyalakan server

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

> **Penting:** opsi `--host=0.0.0.0` wajib dipakai agar server bisa diakses dari HP
> melalui Expo Go. Tanpa opsi ini, server hanya mendengarkan `127.0.0.1` dan HP tidak
> akan bisa terhubung.

### 8. Catat alamat IP laptop

Buka terminal baru, jalankan `ipconfig`, lalu catat **IPv4 Address** pada adapter Wi-Fi.
Alamat ini dipakai pada berkas `.env.local` di proyek mobile (salin dari `.env.example`):

```env
EXPO_PUBLIC_API_URL=http://<IPv4-LAPTOP>:8000/api
```

Pastikan HP dan laptop terhubung ke jaringan Wi-Fi yang sama.

---

## Daftar Endpoint

Base URL: `http://<IP-LAPTOP>:8000/api`

| Method | Endpoint                | Akses        | Keterangan                                 |
|--------|-------------------------|--------------|--------------------------------------------|
| POST   | `/register`             | Publik       | Daftar akun baru, mengembalikan token      |
| POST   | `/login`                | Publik       | Login, mengembalikan token                 |
| GET    | `/profile`              | Bearer Token | Ambil data profil pengguna                 |
| PUT    | `/profile`              | Bearer Token | Update nama, email, dan no_hp              |
| PUT    | `/profile/password`     | Bearer Token | Ganti kata sandi (verifikasi sandi lama)   |
| POST   | `/profile/photo`        | Bearer Token | Upload foto, dikonversi ke WebP (maks 5 MB)|
| GET    | `/profile/photo/{id}`   | Publik       | Sajikan foto profil hasil konversi         |
| POST   | `/logout`               | Bearer Token | Hapus token yang sedang dipakai            |

### Format respons

**Register** — `201 Created`

```json
{
  "success": true,
  "message": "Registrasi akun berhasil.",
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Fajri",
    "email": "fajri@minimarket.id",
    "no_hp": "081234567890",
    "foto": null
  }
}
```

**Login** — `200 OK` (format sama seperti register).
Jika email/password salah → `401`:

```json
{
  "success": false,
  "message": "Email atau kata sandi yang Anda masukkan salah."
}
```

**Validasi gagal** — `422 Unprocessable Entity`

```json
{
  "message": "Email ini sudah terdaftar, silakan gunakan email lain.",
  "errors": {
    "email": ["Email ini sudah terdaftar, silakan gunakan email lain."]
  }
}
```

**Endpoint terproteksi** wajib menyertakan header:

```
Authorization: Bearer <token>
Accept: application/json
```

---

## Contoh Pengujian (Postman / Thunder Client)

**Register**

```
POST http://127.0.0.1:8000/api/register
Content-Type: application/json

{
  "name": "Fajri",
  "email": "fajri@minimarket.id",
  "no_hp": "081234567890",
  "password": "rahasia123",
  "password_confirmation": "rahasia123"
}
```

**Login**

```
POST http://127.0.0.1:8000/api/login
Content-Type: application/json

{
  "email": "fajri@minimarket.id",
  "password": "rahasia123"
}
```

**Profile** (ganti `<TOKEN>` dengan token hasil login)

```
GET http://127.0.0.1:8000/api/profile
Authorization: Bearer <TOKEN>
Accept: application/json
```

---

## Struktur Berkas Penting

```
app/
├── Http/Controllers/Api/AuthController.php   # register, login, profile, logout
└── Models/User.php                           # Fillable: name, email, no_hp, foto, password
                                              # Trait: HasApiTokens (Sanctum)
database/migrations/
├── 0001_01_01_000000_create_users_table.php  # kolom no_hp & foto
└── 2026_11_18_104438_create_personal_access_tokens_table.php

routes/api.php                                # 4 endpoint API
config/cors.php                               # allowed_origins: ['*']
```

---

## Struktur Tabel `users`

| Kolom                       | Tipe            | Keterangan                                |
|-----------------------------|-----------------|-------------------------------------------|
| `id`                        | bigint unsigned | Primary key                               |
| `name`                      | varchar(255)    | Nama lengkap                              |
| `email`                     | varchar(255)    | Unik                                      |
| `no_hp`                     | varchar(20)     | Nullable — nomor HP / WhatsApp            |
| `foto`                      | varchar(255)    | Nullable — disiapkan untuk modul lanjutan |
| `password`                  | varchar(255)    | Ter-hash otomatis (cast `hashed`)         |
| `created_at` / `updated_at` | timestamp       | —                                         |

---

## Troubleshooting

| Masalah | Penyebab & Solusi |
|---|---|
| `Unknown database 'db_minimarket_app'` | Database belum dibuat — jalankan langkah 3 |
| HP tidak bisa terhubung ke API | Server dijalankan tanpa `--host=0.0.0.0`, atau IP di `src/config/api.ts` tidak cocok dengan hasil `ipconfig`, atau Windows Firewall memblokir port 8000 |
| `no_hp` bernilai `NULL` padahal sudah diisi | `no_hp` belum terdaftar pada atribut `#[Fillable]` di `app/Models/User.php` |
| `Route [login] not defined` saat akses `/api/profile` | Kirim header `Accept: application/json` agar Laravel membalas JSON, bukan redirect |
| Token tidak valid setelah login ulang | Perilaku normal: login baru menghapus token lama (`$user->tokens()->delete()`) |
| `php artisan migrate` gagal koneksi | Pastikan MySQL berjalan dan kredensial di `.env` benar |

---

## Deploy Online ke Render (Docker)

Laravel tidak punya runtime native di Render, sehingga aplikasi ini dideploy
sebagai **Docker Web Service** memakai `Dockerfile` di repo ini
(nginx + php-fpm 8.4 + Postgres).

### 1. Generate `APP_KEY`

```bash
php artisan key:generate --show
```

Salin hasilnya (format `base64:...`). Nilai ini **wajib diisi manual** di Render:
`generateValue` milik Render tidak menghasilkan format yang diterima Laravel.

### 2. Deploy lewat Blueprint (paling cepat)

1. Render Dashboard → **New** → **Blueprint**
2. Pilih repository ini
3. Saat diminta, isi `APP_KEY` dan `APP_URL`
   (`APP_URL` = `https://<nama-service>.onrender.com`)
4. **Deploy**

### 3. Variabel lingkungan wajib

| Key | Nilai |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:...` |
| `APP_URL` | `https://<service>.onrender.com` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | connection string Render Postgres |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `LOG_CHANNEL` | `stderr` |

### Catatan Render free plan

1. **Filesystem ephemeral.** File di disk hilang setiap redeploy/restart/spin-down.
   Karena itu **foto profil disimpan ke tabel `profile_photos`**, bukan ke
   `storage/app/public`. Kolom `users.foto` berisi URL
   `/api/profile/photo/{id}` sehingga foto tetap tampil setelah restart.
2. **Spin-down setelah 15 menit idle**, butuh ~1 menit untuk bangun kembali.
   Aplikasi mobile memakai timeout 60 detik untuk mengakomodasi ini. Agar
   responsif saat demo, ping `https://<service>.onrender.com/up` secara berkala.
3. **Render Postgres free kedaluwarsa 30 hari** setelah dibuat — buat database
   baru bila jadwal demo masih jauh.

### Health check

`GET /up` mengembalikan `200` bila aplikasi berjalan.

### Konversi foto (Modul 12)

Foto di-crop 1:1, diperkecil maksimal 512×512 px, lalu dikonversi ke **WebP**
kualitas 80 memakai ekstensi GD (`imagewebp`). Rotasi EXIF dari kamera HP
diperbaiki otomatis. Seluruh proses diverifikasi oleh `tests/Feature/ProfilePhotoTest.php`.

---

## Lisensi

Project praktikum ini dibuat untuk keperluan akademik.
Framework Laravel dilisensikan di bawah [MIT license](https://opensource.org/licenses/MIT).

