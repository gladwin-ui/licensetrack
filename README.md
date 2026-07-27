# LicenseTrack

LicenseTrack adalah sebuah sistem manajemen lisensi dan gateway pengingat (reminder) otomatis berbasis web. Sistem ini dirancang untuk memantau masa aktif lisensi perusahaan dan mengirimkan notifikasi pengingat via WhatsApp kepada PIC (Person in Charge) terkait sebelum lisensi tersebut kedaluwarsa.

## 🚀 Tech Stack

- **Framework Backend:** Laravel 11 (PHP 8.2+)
- **Frontend / Styling:** Tailwind CSS, Alpine.js, Blade Templates
- **Database:** MySQL
- **WhatsApp Gateway:** Fonnte API
- **Libraries Lainnya:** SweetAlert2 (untuk alerts/pop-ups), Chart.js (untuk visualisasi data)

## 🏗 System Architecture

Alur arsitektur sistem LicenseTrack bekerja secara otomatis di belakang layar menggunakan antrean (queue) dan penjadwalan (scheduler) dari Laravel:

1. **Pencatatan Lisensi:** Pengguna memasukkan data lisensi dan kontak PIC ke dalam sistem. Sistem secara otomatis menghitung dan membuat jadwal pengingat (H-14, H-7, H-3, H-1, dan Hari H) berdasarkan tanggal kedaluwarsa dan pengaturan waktu kirim.
2. **Scheduler (Cron Job):** Laravel Scheduler (`php artisan schedule:work`) akan berjalan setiap menit untuk memeriksa jadwal di tabel `reminder_logs`.
3. **Queue System:** Jika ada jadwal pengingat yang sudah waktunya dikirim, sistem akan memasukkannya ke dalam antrean (Queue) agar tidak membebani server saat pengguna sedang mengakses web.
4. **WhatsApp Gateway (Fonnte):** Queue Worker (`php artisan queue:work`) akan mengeksekusi antrean tersebut dan memanggil layanan `FonnteGateway` untuk mengirimkan pesan via API HTTP ke server Fonnte, yang kemudian meneruskannya ke nomor WhatsApp PIC terkait.
5. **Pembaruan Status:** Setelah pesan berhasil atau gagal dikirim, sistem akan mencatat hasilnya di menu "Riwayat Reminder" dan meng-update status koneksi *device* Fonnte secara *real-time* di *dashboard*.

## ✨ Key Features

- **Manajemen Lisensi Terpusat:** Pantau seluruh lisensi dari berbagai vendor dalam satu dasbor informatif (Lisensi Aman, Waspada, Kritis, dan Expired).
- **Multi-PIC per Lisensi:** Satu lisensi dapat memiliki banyak kontak PIC. Sistem dapat mengirimkan pengingat ke seluruh kontak sekaligus.
- **Automated WhatsApp Reminders:** Pengiriman pesan otomatis melalui WhatsApp yang terjadwal (H-14, H-7, H-3, H-1, H-0) tanpa perlu campur tangan manual.
- **Kustomisasi Pesan & Waktu:** Template pesan pengingat dan waktu pengiriman harian (misal: jam 09:00 WIB) dapat diatur secara dinamis melalui menu Pengaturan.
- **Status Koneksi Real-time:** Memantau status perangkat WhatsApp (terkoneksi atau terputus) secara langsung dari Header halaman dan halaman Pengaturan.
- **Modern & Responsif UI:** Antarmuka pengguna yang dirancang dengan desain estetis, memanjakan mata, mendukung animasi transisi halus, serta sepenuhnya responsif di berbagai ukuran perangkat.

## 📁 Project Structure

Struktur direktori mengikuti standar arsitektur Laravel, dengan beberapa komponen inti khusus LicenseTrack:

- `app/Models/` : Berisi model utama aplikasi seperti `License`, `LicenseContact`, `ReminderLog`, `Setting`, dll.
- `app/Http/Controllers/` : Berisi logika sistem untuk manajemen *dashboard*, lisensi, pengaturan, dan riwayat.
- `app/Jobs/` : Berisi *job* antrean, khususnya `SendWhatsappMessage` untuk memproses pengiriman API ke Fonnte secara *asynchronous*.
- `app/Console/Commands/` : Berisi logika eksekusi untuk me- *load* jadwal harian pengingat ke sistem *queue*.
- `app/Services/WhatsApp/` : Folder layanan *core* yang menangani komunikasi integrasi API Fonnte (`FonnteGateway`) dan pengecekan status perangkat (`FonnteDeviceChecker`).
- `resources/views/` : Kumpulan antarmuka pengguna web (Blade templates) yang dibangun dengan Tailwind CSS dan Alpine.js.

## ⚙️ Installation and Run Guide

Ikuti langkah-langkah di bawah ini untuk menjalankan LicenseTrack di server atau *local environment* Anda:

### 1. Persyaratan Sistem
- PHP >= 8.2
- Composer
- Node.js & npm
- MySQL / MariaDB

### 2. Instalasi Proyek
```bash
# Clone repository ini (jika dari git)
# git clone <repository-url>

# Masuk ke direktori root proyek
cd licensetrack

# Install dependensi PHP
composer install

# Install dependensi Frontend
npm install
npm run build
```

### 3. Konfigurasi Database & Lingkungan
Duplikat file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Generate *Application Key*:
```bash
php artisan key:generate
```
Buka file `.env` dan konfigurasikan kredensial koneksi *database* Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_anda
DB_USERNAME=root
DB_PASSWORD=

# Pastikan konfigurasi Queue menggunakan database
QUEUE_CONNECTION=database
```

### 4. Setup Database & Migrasi
Buat *database* MySQL terlebih dahulu (sesuaikan dengan nama yang Anda isi di `DB_DATABASE`):

```sql
CREATE DATABASE nama_database_anda;
```

Setelah database berhasil dibuat, jalankan perintah berikut untuk mengeksekusi migrasi tabel beserta data *seeder* default:
```bash
php artisan migrate --seed
```

### 5. Menjalankan Aplikasi
Untuk menjalankan LicenseTrack secara utuh beserta seluruh fitur otomatisasinya, Anda perlu menjalankan **tiga perintah** di tiga terminal yang berbeda secara bersamaan:

**Terminal 1 (Menjalankan Web Server):**
```bash
php artisan serve
```
*(Akses aplikasi melalui http://localhost:8000)*

**Terminal 2 (Menjalankan Queue Worker):**
Perintah ini bertugas mengeksekusi antrean latar belakang, terutama proses pengiriman WhatsApp API.
```bash
php artisan queue:work
```

**Terminal 3 (Menjalankan Scheduler):**
Perintah ini bertugas untuk memeriksa jadwal *reminder* secara *real-time* dari jam ke jam.
```bash
php artisan schedule:work
```

### 6. Pengaturan Gateway WhatsApp
- Login ke dalam aplikasi web LicenseTrack.
- Buka menu **Konfigurasi & Sistem** > **Pengaturan**.
- Masukkan **Token API** yang Anda dapatkan dari *dashboard* [Fonnte](https://fonnte.com/).
- Klik "Simpan Pengaturan". Jika token valid, status *device* di layar (dan indikator "WA" di pojok kanan atas) akan berubah menjadi hijau dan menunjukkan "Terkoneksi".
