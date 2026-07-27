# LicenseTrack

**Sistem Manajemen & Pengingat Kedaluwarsa Lisensi**
Project Magang — PT Hariff Dipa Persada

---

## Deskripsi Proyek

LicenseTrack adalah aplikasi web internal untuk mencatat seluruh lisensi/sertifikasi/key perusahaan beserta file pendukungnya, memantau masa berlakunya, dan (di Fase berikutnya) mengirim pengingat WhatsApp otomatis ke PIC pemilik lisensi menjelang tanggal kedaluwarsa.

**Masalah yang diselesaikan:** Lisensi sering lewat masa berlaku karena tidak ada yang memantau secara terpusat, dan pengingat manual mudah terlupakan.

---

## Tech Stack

| Komponen | Teknologi |
|---|---|
| Framework | Laravel 11 (Blade + Tailwind CSS) |
| Auth | Laravel Breeze (Blade stack) |
| Database | MySQL |
| Frontend | Alpine.js (bawaan Breeze) + Vite |
| Desain / UI | Tailwind CSS, Google Font Sora (Judul), Figtree (Body Text) |
| File Storage | Local private disk (`storage/app/licenses/`) |
| WhatsApp Gateway | LogGateway (stub), Meta Cloud API (Live), atau Fonnte Gateway |
| Timezone | Asia/Jakarta |

---

## Cara Setup

```bash
# 1. Clone repositori
git clone <repo-url>
cd licensetrack

# 2. Install dependensi PHP
composer install

# 3. Install dependensi Node.js
npm install

# 4. Konfigurasi environment
cp .env.example .env
# Edit .env: isi DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Generate app key
php artisan key:generate
```

> [!CAUTION]
> **`php artisan key:generate` HANYA untuk instalasi baru yang masih kosong.**
> Jangan pernah menjalankannya pada sistem yang sudah berisi data.
>
> `APP_KEY` adalah kunci enkripsi untuk `license_key` dan token gateway Fonnte.
> Jika `APP_KEY` berubah atau hilang, seluruh data terenkripsi **tidak dapat
> dipulihkan** — backup database sekalipun tidak menolong, karena isinya
> ciphertext tanpa kunci pembuka.
>
> Simpan `APP_KEY` di tempat aman dan **terpisah** dari lokasi backup database.
> Saat memindahkan aplikasi ke server lain, salin `APP_KEY` yang lama —
> jangan generate yang baru.

```bash
# 6. Buat database MySQL
# (jalankan di MySQL client Anda)
CREATE DATABASE licensetrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 7. Jalankan migrasi
php artisan migrate

# 8. Buat admin utama
php artisan admin:create

# 9. (Opsional) Data lisensi contoh untuk uji coba
php artisan db:seed --class=LicenseSeeder

# 10. Build aset frontend
npm run build

# 11. Jalankan server development
php artisan serve
```

Akses aplikasi di: `http://localhost:8000`

---

## Menjalankan Queue & Scheduler (WAJIB)

> [!IMPORTANT]
> Tanpa queue worker dan scheduler, **tidak ada reminder yang akan terkirim**.
> Aplikasi tetap dapat dibuka dan data tetap tersimpan, tetapi fungsi utamanya
> tidak berjalan — dan tidak ada pesan error yang muncul.

### Development (lokal)

Jalankan di terminal terpisah:

```bash
# Terminal 1 — web server
php artisan serve

# Terminal 2 — queue worker (memproses pengiriman WhatsApp)
php artisan queue:work

# Terminal 3 — scheduler (memicu pengecekan jadwal)
php artisan schedule:work
```

### Produksi

**1. Scheduler — pasang cron**

Jalankan `crontab -e`, tambahkan satu baris:

```
* * * * * cd /path/ke/licensetrack && php artisan schedule:run >> /dev/null 2>&1
```

Cron berjalan tiap menit; Laravel sendiri yang menentukan command mana yang
waktunya tiba. Jangan membuat cron terpisah per command.

**2. Queue worker — pasang Supervisor**

`php artisan queue:work` harus berjalan terus-menerus dan otomatis hidup
kembali jika mati. Buat `/etc/supervisor/conf.d/licensetrack-worker.conf`:

```ini
[program:licensetrack-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/ke/licensetrack/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/ke/licensetrack/storage/logs/worker.log
stopwaitsecs=3600
```

Lalu aktifkan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start licensetrack-worker:*
```

**3. Setelah deploy ulang kode**

Queue worker menyimpan kode lama di memori. Wajib restart setiap kali kode
diperbarui:

```bash
php artisan queue:restart
```

### Cara memastikan keduanya berjalan

- Cek worker: `sudo supervisorctl status licensetrack-worker:*`
- Cek antrean menumpuk: lihat tabel `jobs` — jika terus bertambah dan tidak
  pernah berkurang, worker tidak berjalan
- Cek pengiriman: halaman **Riwayat Reminder** harus bertambah saat ada
  jadwal yang## Peran Pengguna

Aplikasi mengenal dua peran. Keduanya memiliki akses penuh ke fitur lisensi, reminder, riwayat, dan pengaturan. Yang membedakan hanya kewenangan mengelola akun.

| Kemampuan | Super Admin | Administrator |
|---|---|---|
| Dashboard, lisensi, reminder, riwayat, pengaturan | ✅ | ✅ |
| Mengakses menu Kelola Admin | ✅ | ❌ |
| Menyetujui / menolak pendaftaran | ✅ | ❌ |
| Mengaktifkan / menonaktifkan administrator | ✅ | ❌ |
| Mengangkat administrator menjadi Super Admin | ✅ | ❌ |
| Mengirim link reset password administrator lain | ✅ | ❌ |

> Pembatasan ini ditegakkan di tingkat route, bukan sekadar menyembunyikan menu.
> Administrator biasa yang mengakses `/users` secara langsung akan menerima
> HTTP 403.

---

## Alur Akun

1. **Super Admin pertama** dibuat lewat `php artisan admin:create`, atau otomatis dari pendaftaran pertama saat tabel `users` masih kosong.
2. **Administrator baru** mendaftar sendiri melalui halaman `/register`.
3. Akun tersimpan dengan status `pending` dan belum dapat digunakan untuk login.
4. Super Admin meninjaunya di **Kelola Admin → Pengajuan Pendaftaran**, lalu menyetujui atau menolak.
5. Setelah disetujui, administrator dapat masuk seperti biasa.

Aplikasi tidak menyediakan akun default. Untuk menghapus seluruh akun dan memulai dari nol: `php artisan users:reset`.

### Status Akun

| Status | Arti | Dapat login |
|---|---|---|
| `pending` | Baru mendaftar, menunggu persetujuan | ❌ |
| `active` | Disetujui dan aktif | ✅ |
| `rejected` | Pengajuan ditolak Super Admin | ❌ |
| `inactive` | Pernah aktif, lalu dinonaktifkan | ❌ |

### Pengalihan Peran Super Admin

Sistem menjaga agar selalu tersedia minimal satu Super Admin aktif.

Sebelum seorang Super Admin meninggalkan organisasi, angkat administrator lain melalui **Kelola Admin → Daftar Administrator → Jadikan Super Admin**.

> Jika tidak tersisa Super Admin aktif, pendaftaran baru tidak dapat disetujui dan pemulihan hanya mungkin melalui `php artisan admin:create` di server atau perubahan langsung pada database.

---

## Backup

Tiga komponen wajib di-backup. Kehilangan salah satunya membuat dua lainnya tidak berguna:

| Komponen | Isi | Catatan |
|---|---|---|
| Database | Data lisensi, PIC, jadwal, audit log | Rutin harian |
| `storage/app/licenses/` | **File sertifikat & dokumen lisensi** | TIDAK ikut dalam backup database |
| `APP_KEY` (dari `.env`) | Kunci enkripsi | Simpan terpisah dari backup database |

> Kesalahan paling umum: hanya membackup database. File sertifikat berada di filesystem, bukan di dalam database — hilang bersama server jika tidak ikut dibackup.

---

## Struktur Database

```
users
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  name                varchar(255)
  email               varchar(255) unique
  phone               varchar(255) nullable    -- nomor WhatsApp ternormalisasi 628xxx
  password            varchar(255)
  status              enum('pending','active','rejected','inactive') default 'pending'
  is_super_admin      boolean default false
  approved_by         bigint unsigned nullable (FK users)
  approved_at         timestamp nullable
  last_login_at       timestamp nullable
  timestamps

licenses
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  name                varchar(255)
  vendor              varchar(255) nullable
  description         text nullable
  license_key         text nullable (encrypted)
  start_date          date
  end_date            date (INDEX)
  status              enum('active','renewed','cancelled') default 'active'
  created_by          bigint unsigned nullable (FK users, setNull on delete)
  message_template_id bigint unsigned nullable (FK message_templates, restrict on delete)
  message_intro       text nullable
  message_closing     text nullable
  timestamps
  deleted_at          timestamp nullable (softDelete)

message_templates
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  name                varchar(100) unique
  intro               text
  closing             text
  is_default          boolean default false (INDEX)
  created_by          bigint unsigned nullable (FK users, setNull on delete)
  timestamps

license_files
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  license_id          bigint unsigned (FK licenses, cascade on delete)
  path                varchar(255)
  original_name       varchar(255)
  mime_type           varchar(255)
  size                bigint
  timestamps

license_contacts
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  license_id          bigint unsigned (FK licenses, cascade on delete)
  name                varchar(255)
  phone               varchar(255)
  is_primary          boolean default false
  timestamps

reminder_logs
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  license_id          bigint unsigned (FK licenses, cascade on delete)
  milestone           integer
  scheduled_at        timestamp
  status              enum('pending','sent','failed','skipped') default 'pending'
  sent_at             timestamp nullable
  timestamps
  -- UNIQUE(license_id, milestone)
  -- INDEX(status, scheduled_at)

whatsapp_messages
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  reminder_log_id     bigint unsigned nullable (FK reminder_logs, setNull on delete)
  license_contact_id  bigint unsigned nullable (FK license_contacts, setNull on delete)
  phone               varchar(255)
  body                text
  status              enum('pending','sent','failed') default 'pending'
  wamid               varchar(255) nullable (INDEX)
  error_message       text nullable
  sent_at             timestamp nullable
  timestamps

settings
  key                 varchar(255) PRIMARY KEY
  value               text nullable
  timestamps

audit_logs
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  user_id             bigint unsigned nullable (FK users, setNull on delete)
  action              varchar(255) (INDEX)
  auditable_type      varchar(255) nullable
  auditable_id        bigint unsigned nullable
  ip_address          varchar(45) nullable
  user_agent          text nullable
  context             text nullable
  created_at          timestamp
  -- INDEX(action, created_at)

password_reset_tokens
  email               varchar(255) PRIMARY KEY
  token               varchar(255)
  created_at          timestamp nullable

urgent_alert_logs
  id                  bigint unsigned AUTO_INCREMENT PRIMARY KEY
  license_id          bigint unsigned (FK licenses, cascade on delete)
  alert_date          date
  slot                enum('morning','afternoon')
  sent_at             timestamp
  -- UNIQUE(license_id, alert_date, slot)
```

---

## Halaman yang Tersedia

| Route | Halaman | Akses |
|---|---|---|
| `/login` | Masuk | Publik |
| `/register` | Daftar | Publik |
| `/forgot-password`, `/reset-password/{token}` | Lupa & atur ulang password | Publik |
| `/dashboard` | Dashboard | Terautentikasi |
| `/licenses`, `/licenses/{id}` | Daftar & detail lisensi | Terautentikasi |
| `/reminders` | Riwayat reminder | Terautentikasi |
| `/settings` | Pengaturan | Terautentikasi |
| `/users` | Kelola Admin | **Super Admin** |
| `/audit-logs` | Audit log | Terautentikasi |
| `/profile` | Profil sendiri | Terautentikasi |

---

## Autentikasi & Keamanan

Sistem LicenseTrack dilengkapi dengan sistem autentikasi multi-admin internal, manajemen admin, pencatatan audit log, dan hardening keamanan.

### Reset Database Akun & Membuat Admin Pertama

Jika Anda ingin mereset seluruh database pengguna (users) dan memulai dari awal secara aman, jalankan:

```bash
php artisan users:reset
```

> [!WARNING]
> Perintah ini akan menghapus **seluruh** akun pengguna di tabel `users`. Referensi user pada lisensi (`licenses.created_by`) dan log audit (`audit_logs.user_id`) akan di-set menjadi `NULL` sehingga data operasional tetap utuh.

Perintah `users:reset` di atas akan memandu Anda secara interaktif untuk membuat **Super Admin Pertama** dengan menanyakan:
1. Nama administrator
2. Email administrator
3. Nomor WhatsApp (normalisasi otomatis ke format `628xxxxxxxx`)
4. Kata sandi (minimal 8 karakter)

Alternatif lainnya, Anda dapat membuat Super Admin baru tanpa mereset sistem melalui perintah:

```bash
php artisan admin:create
```

### Konfigurasi SMTP Mail untuk Produksi (WAJIB)

Di lingkungan pengembangan (development), parameter di `.env` diset ke `MAIL_MAILER=log`, yang berarti email reset password dan pemberitahuan persetujuan tidak benar-benar terkirim, melainkan hanya ditulis ke file log `storage/logs/laravel.log`.

Untuk lingkungan produksi, wajib melakukan setup SMTP yang valid di `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io (atau host SMTP perusahaan)
MAIL_PORT=2525
MAIL_USERNAME=username_anda
MAIL_PASSWORD=password_anda
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@hariff.co.id"
MAIL_FROM_NAME="LicenseTrack — PT Hariff Dipa Persada"
```

### Keamanan Aplikasi

Aplikasi menerapkan praktik terbaik keamanan siber berikut:

1. **Otorisasi Kelola Admin Ditegakkan Lewat Middleware:** Pembatasan akses halaman Kelola Admin serta seluruh aksi pendukung (Setujui, Tolak, Aktifkan, Menonaktifkan, Angkat Super Admin, Reset Link) dikunci menggunakan middleware di tingkat route (`EnsureUserIsSuperAdmin`), bukan hanya disembunyikan dari UI menu. Setiap percobaan akses tanpa hak akan ditolak dengan **HTTP 403** (halaman error rapi berbahasa Indonesia) dan dicatat ke Audit Log (`access.denied`).
2. **Perlindungan Mass Assignment:** Kolom sensitif seperti `is_super_admin`, `status`, `approved_by`, dan `approved_at` dikeluarkan dari properti `$fillable` model `User`. Ini mencegah adanya eksploitasi parameter HTTP untuk memalsukan status akun atau menaikkan hak akses saat melakukan pendaftaran.
3. **Rate Limiting & Throttling:**
   - Formulir pendaftaran publik (/register) dibatasi rate-limit maksimal **3 pendaftaran per jam per alamat IP**.
   - Percobaan masuk (/login) dibatasi maksimal **5 kali kegagalan** berturut-turut sebelum dikunci sementara dengan sisa waktu tunggu.
4. **Pencatatan Audit Log Real-time:** Setiap aksi krusial dicatat lengkap beserta data pengguna (pelaku) dan alamat IP-nya:
   - Login sukses & gagal (beserta IP pengirim)
   - Percobaan akses ditolak ke halaman Kelola Admin (`access.denied`)
   - Pendaftaran akun baru (`user.registered`)
   - Persetujuan & penolakan pendaftaran (`user.approved`, `user.rejected`)
   - Aktivasi, deaktivasi, & pengangkatan Super Admin
   - Reset kata sandi & unduh dokumen lisensi sensitif

---

## Keamanan Token Fonnte (Gateway WhatsApp)

Token Fonnte dikonfigurasi dari halaman **Pengaturan** tanpa perlu menyentuh file server.

**Cara kerja penyimpanan:**
- Token tersimpan **terenkripsi** di tabel `settings` menggunakan enkripsi Laravel (`APP_KEY`).
- Ketika token di database terisi, sistem memakai token tersebut. Jika kosong, fallback ke `.env` (untuk kompatibilitas migrasi).
- Token **tidak pernah ditampilkan utuh** di antarmuka web, response HTML, maupun log — hanya 6 karakter terakhirnya.

> [!CAUTION]
> Enkripsi token bergantung pada `APP_KEY` di `.env`. **Jangan simpan backup database dan file `.env` di lokasi yang sama.** Jika keduanya bocor bersamaan, enkripsi menjadi tidak berguna.

---

## Setup Fonnte Gateway

1. Daftar di https://fonnte.com
2. Login → klik "Add Device"
3. Beri nama device (mis. "LicenseTrack")
4. Scan QR code menggunakan WhatsApp di HP kamu
5. Setelah connected, masuk ke halaman device → salin TOKEN
6. Isi .env atau isi di halaman Pengaturan:
   WA_GATEWAY=fonnte
   FONNTE_TOKEN=token_yang_disalin
7. Test lewat halaman Pengaturan → Kirim Test

PENTING: HP harus tetap menyala dan WhatsApp tidak boleh logout. Kalau HP mati atau logout, pengiriman reminder akan gagal sampai HP kembali online.

---

## Template Pesan Kustom & Placeholder

Sistem memiliki tabel `message_templates` untuk mengelola template kalimat pengingat WhatsApp. Pengguna dapat memilih mode pengingat pada setiap lisensi:
1. **Mode Standard:** Menggunakan teks pengingat bawaan sistem.
2. **Mode Template:** Menggunakan template pesan tersimpan di tabel `message_templates`.
3. **Mode Custom:** Menulis kalimat pembuka (`message_intro`) dan penutup (`message_closing`) kustom yang unik untuk lisensi tersebut.

### Daftar Placeholder yang Didukung:
- `{perusahaan}`: Nama perusahaan dari Pengaturan (mis. PT Hariff Dipa Persada)
- `{nama_pic}`: Nama PIC penerima pesan
- `{nama_lisensi}`: Nama lisensi/sertifikat
- `{vendor}`: Nama vendor penerbit
- `{tanggal_mulai}`: Tanggal mulai berlaku lisensi (format Indonesia)
- `{tanggal_berakhir}`: Tanggal berakhir lisensi (format Indonesia)
- `{sisa_hari}`: Sisa hari sebelum kadaluwarsa (angka)

> [!IMPORTANT]
> Kalimat pembuka dan penutup kustom hanya berlaku untuk pengingat reguler sebelum kedaluwarsa (milestone >= 0) dan pengiriman manual ad-hoc. Pesan overdue (milestone < 0) dan urgent alert harian tetap menggunakan teks bawaan sistem agar bahasa tetap relevan.

