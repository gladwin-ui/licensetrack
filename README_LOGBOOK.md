# Logbook Pengembangan LicenseTrack — 13 Juli s/d 12 Agustus 2026

Dokumentasi harian aktivitas dan capaian pembangunan web LicenseTrack
(Sistem Manajemen & Pengingat Kedaluwarsa Lisensi), PT Hariff / Hariff Defense.
Disusun berdasarkan riwayat percakapan dan aktivitas pengembangan.

---

## 13 Juli 2026 — FASE 1 & FASE 2: Fondasi & Reminder Engine (Day 1)

### Aktivitas
- Setup awal proyek Laravel menggunakan starter kit otentikasi berbasis **Blade + Tailwind CSS** (Laravel Breeze).
- Mengatur konfigurasi lokal database MySQL, zona waktu ke `Asia/Jakarta`, dan lokalisasi bahasa ke `id`.
- Menambahkan konfigurasi disk penyimpanan privat (`licenses`) untuk menyimpan file lisensi sensitif di `storage/app/licenses` (tanpa link publik).
- Merancang migrasi database utama: tabel `licenses` (dengan enkripsi kolom `license_key`), `license_files`, `license_contacts`, `reminder_logs` (dengan unique constraint pada license & milestone), dan `settings`.
- Menyusun model `License` beserta accessor `daysRemaining` dan `healthStatus`.
- Membangun halaman dashboard awal dengan kartu KPI status lisensi dan tabel monitoring utama.
- Membangun **Reminder Engine** untuk penjadwalan reminder otomatis secara pra-generasi (`pre-generate`) pada tabel `reminder_logs` saat lisensi dibuat atau diperbarui, lengkap dengan logika anti-catch-up storm (`skipped` status).
- Membuat perintah scheduler `php artisan reminders:dispatch` untuk mengirim antrian pengingat yang jatuh tempo dan mendaftarkan *job* asinkron `SendWhatsAppReminder` ke antrian (`queue`).
- Membangun antarmuka gateway pengiriman pesan `WhatsAppGateway` dengan dua implementasi: `MetaCloudGateway` dan `LogGateway`.

### Capaian
- Kerangka kerja sistem, database, halaman dashboard dasar, dan mesin scheduler pengiriman WhatsApp (`LogGateway`) selesai dibangun dan berfungsi.

---

## 14 Juli 2026 — Debugging Pengiriman & Pengaturan Jam (Day 2)

### Aktivitas
- Menganalisis dan memperbaiki masalah pengiriman reminder manual (tombol proses antrian sekarang) yang terkadang masuk dan terkadang tidak.
- Menambahkan integrasi dinamis jam pengiriman pengingat di pengaturan web (`config/reminder.php`) serta menangani regenerasi `reminder_logs` ketika jam reminder diganti oleh admin secara langsung (misal diubah ke jam 9.45).
- Memeriksa penyesuaian milestone pengingat agar sesuai dengan target PRD.

### Capaian
- Pengaturan jam pengiriman reminder dari halaman pengaturan dinamis berfungsi dengan benar dan langsung memperbarui antrean scheduler yang terjadwal.

---

## 15 Juli 2026 — UI Polish & Penggabungan Aksi (Day 3)

### Aktivitas
- Menguji pengiriman terjadwal dan memperbaiki sinkronisasi waktu pengiriman reminder pada database lokal.
- Menyederhanakan alur aksi edit lisensi dengan menghapus tombol edit terpisah dan menggabungkannya langsung di dalam halaman detail laporan lisensi agar UI lebih bersih.
- Menambahkan analisis kebutuhan webhook untuk status WhatsApp.
- Mengerjakan perbaikan visual berdasarkan `PROMPT-UI-Polish-Tahap2.md`: mengecilkan ukuran logo di sidebar dan memposisikan nama aplikasi `LicenseTrack` sejajar dengan logo diamond.

### Capaian
- Alur edit data lisensi lebih ringkas, visual area navigasi sidebar lebih rapi dengan penempatan logo yang proporsional.

---

## 17 Juli 2026 — Deployment Guide & Verifikasi Pesan (Day 4)

### Aktivitas
- Menyusun panduan deployment otomatisasi pengiriman pesan pengingat menggunakan cron job di server hosting lokal (`php artisan schedule:run`).
- Melakukan verifikasi dan pelacakan pengiriman pesan WhatsApp untuk memastikan tidak ada keterlambatan pengiriman atau kegagalan koneksi ke API gateway.

### Capaian
- Panduan integrasi scheduler di tingkat server lokal berhasil diselesaikan untuk deployment di masa mendatang.

---

## 20 Juli 2026 — Redesign Airy & Analisis Duplikasi (Day 5)

### Aktivitas
- Menganalisis masalah log pengiriman pesan ganda (pesan terkirim 2 kali untuk lisensi tertentu seperti Claude Pro).
- Menerapkan pembaruan antarmuka visual berdasarkan instruksi `PROMPT-Redesign-Airy.md` dan mockup desain: memperjelas visibilitas tombol aksi tabel, tombol navigasi halaman (sebelumnya/selanjutnya), serta menerapkan gaya tata letak modern.

### Capaian
- Desain antarmuka dashboard dan daftar lisensi lebih mewah (Airy Design), serta masalah duplikasi pengiriman manual/otomatis berhasil diatasi melalui validasi status log.

---

## 21 Juli 2026 — Perbaikan Kontras & Alur Hapus (Day 6)

### Aktivitas
- Meningkatkan kontras warna pada kartu KPI dashboard agar informasi status lisensi (waspada, kritis, expired) lebih jelas terbaca.
- Memperbaiki alur navigasi setelah admin menghapus lisensi dari dashboard agar tidak secara otomatis berpindah halaman secara membingungkan (disesuaikan agar tetap berada di dashboard/halaman yang relevan).
- Mengerjakan perbaikan visual dan fungsional berdasarkan instruksi di `PROMPT-Dashboard-Perbaikan.md`.

### Capaian
- Readability visual dashboard meningkat secara signifikan dan alur penghapusan lisensi menjadi lebih intuitif.

---

## 22 Juli 2026 — Optimasi Tampilan Responsif & Filter (Day 7)

### Aktivitas
- Menyembunyikan ikon profil pengguna pada mode tampilan responsif (mobile viewport) untuk menghemat ruang navigasi.
- Merapikan tombol filter pada daftar lisensi dan menghilangkan tombol tambah lisensi dari halaman daftar lisensi (tambah lisensi hanya dapat diakses melalui tombol utama di dashboard).
- Memperbaiki masalah teks/keterangan visual yang bertabrakan di beberapa bagian card ketika dibuka pada layar kecil.

### Capaian
- Tampilan aplikasi web pada perangkat mobile menjadi sangat rapi dan bebas dari elemen bertabrakan.

---

## 23 Juli 2026 — Integrasi Gateway & Filter Multi-Status (Day 8)

### Aktivitas
- Menganalisis kegunaan dropdown status lisensi (active, renewed, cancelled) dan menyembunyikannya pada filter karena hanya data lisensi aktif yang perlu dipantau secara operasional.
- Menerapkan instruksi file `PROMPT-Gateway-Config-Web.md` untuk mengizinkan admin memilih gateway WhatsApp yang aktif (Fonnte API untuk Production, Log Gateway untuk Testing) secara langsung melalui UI Pengaturan.
- Merapikan tampilan halaman pengaturan yang berantakan dan menstabilkan notifikasi pop-up agar muncul secara wajar (tidak berulang-ulang).

### Capaian
- Fitur konfigurasi gateway WhatsApp dinamis via panel admin berjalan dengan stabil dan pengaturan tersimpan ke database settings.

---

## 24 Juli 2026 — Custom Template & Desain Login OTP (Day 9)

### Aktivitas
- Mengubah background halaman login menggunakan file aset `buat bg.jpeg` yang diunggah oleh pengguna.
- Menyesuaikan alur pembuatan admin baru: login hanya dapat dilakukan oleh akun yang diundang (invitation) dan harus melalui verifikasi kode OTP via WhatsApp/Email untuk keamanan tambahan (menghapus registrasi publik).
- Menerapkan fitur **Template Pesan Kustom** (`PROMPT-Template-Pesan-Kustom.md`) yang mengizinkan admin mengedit pesan WhatsApp dengan placeholder dinamis seperti `{nama_lisensi}`, `{sisa_hari}`, dan `{tanggal_berakhir}`.

### Capaian
- Halaman login bergaya kustom selesai, alur pendaftaran admin baru aman dengan approval dan verifikasi OTP, serta template pesan reminder dapat dikustomisasi secara dinamis.

---

## 27 Juli 2026 — Dropdown Kustom & SweetAlert2 (Day 10)

### Aktivitas
- Membangun komponen `<x-custom-select>` kustom berbasis Alpine.js & Tailwind CSS untuk menggantikan seluruh dropdown native `<select>` bawaan sistem operasi agar tampilannya seragam di semua perangkat (Desktop maupun Mobile).
- Mengintegrasikan dropdown kustom ini di seluruh halaman filter dan form (Dashboard, Daftar Lisensi, Form, Pengaturan, Audit Log, dan Riwayat Reminder).
- Menyelaraskan seluruh gaya visual tombol aksi utama (menggunakan gradien merah, lengkungan sudut `rounded-2xl`, dan teks kecil tebal `text-xs`) sesuai dengan desain tombol tambah lisensi di dashboard.
- Memasang pustaka SweetAlert2 untuk menggantikan popup konfirmasi bawaan browser saat admin mengirim pesan reminder secara manual.
- Mengatasi masalah bug notifikasi toast (SweetAlert2) yang terhenti (*stuck*) saat kursor mouse diarahkan ke popup dengan menghapus handler `didOpen` default.
- Menghapus file biner besar `cloudflared.exe` dari pelacakan git dan memasukannya ke file `.gitignore`.

### Capaian
- Antarmuka web terasa lebih premium, seragam, dan ramah pengguna di versi desktop maupun mobile. Bug notifikasi toast SweetAlert berhasil diselesaikan.

---

## 28 Juli 2026 — Poles Tampilan & Indikator Kritis (Day 11)

### Aktivitas
- Menghapus komponen indikator berdenyut merah (*blinking warning dot*) pada kartu status "Kritis" di dashboard atas permintaan pengguna demi kebersihan tata letak UI.
- Menyesuaikan *spacing* (jarak margin atas) di halaman utama dashboard agar layout terangkat dan informasi lebih padat.
- Memperbaiki gaya visual teks logo `LicenseTrack` di area sidebar agar berubah warna menjadi putih saat disorot (*hover*).

### Capaian
- Tata letak dashboard dan sidebar lebih presisi dan bersih.

---

## 30 Juli 2026 — Perbaikan Ikon & Kolom Aksi Admin (Day 12)

### Aktivitas
- Memperbaiki bug tombol tindakan di tabel Kelola Admin (Daftar Administrator) yang tidak muncul. Masalah ini dipicu oleh penggunaan kelas ukuran SVG `w-4.5 h-4.5` (tidak terdaftar di Tailwind CSS, sehingga terender 0px).
- Mengganti ikon aksi pada manajemen admin dengan bentuk yang lebih minimalis dan sederhana.
- Mengubah ukuran ikon aksi admin menjadi `w-4 h-4` dengan padding `p-1.5` serta efek *hover background* berwarna lembut (gray, indigo, red, green) agar seragam dengan gaya tombol aksi lisensi di dashboard.

### Capaian
- Seluruh tombol kontrol administrator (kirim link reset password, jadikan admin utama, aktif/nonaktifkan akun) berfungsi dan tampil dengan gaya visual yang seragam.

---

## 11 Agustus 2026 — Fitur Baru: Template Pesan Setelah Kedaluwarsa (Day 13)

### Aktivitas
- Membuat migrasi database baru untuk menambahkan field `type` pada `message_templates` serta kolom overdue (`overdue_reminder_mode`, `overdue_message_template_id`, `overdue_message_intro`, `overdue_message_closing`) pada tabel `licenses`.
- Mengimplementasikan seeder otomatis untuk template default overdue `'Template Overdue Standar'`.
- Memperbarui model `MessageTemplate` dan `License` dengan relasi dan field fillable baru.
- Menyesuaikan `MessagePlaceholderResolver` untuk mendukung placeholder `{hari_terlewat}` secara dinamis dan memvalidasi penggunaan placeholder agar tidak tertukar antara tipe template.
- Menyesuaikan `ReminderMessageBuilder` untuk menghasilkan teks pengingat overdue yang tepat sesuai konfigurasi lisensi.
- Memperbarui `StoreLicenseRequest` dan `UpdateLicenseRequest` untuk memvalidasi masukan field overdue baru.
- Memperbarui `MessageTemplateController` agar mendukung aksi edit/hapus/set default secara independen per-tipe template.
- Menyusun sub-bagian tab (Reminder vs. Setelah Kedaluwarsa) di halaman Pengaturan Template.
- Menambahkan panel konfigurasi "Pesan Setelah Kedaluwarsa" di form tambah/ubah lisensi lengkap dengan live preview bubble gelembung chat WhatsApp.

### Capaian
- Sistem template pesan overdue (setelah kedaluwarsa) berfungsi penuh dengan pembatasan placeholder yang aman dan UI yang premium serta konsisten.

---

## 12 Agustus 2026 — Fitur Baru: Jadwal Alert Hari-H Dinamis (Day 14)

### Aktivitas
- Membuat migrasi database baru untuk mengubah tipe kolom `slot` pada tabel `urgent_alert_logs` menjadi `varchar(10)` dan melakukan migrasi riwayat pengiriman lama agar menggunakan representasi posisi slot (`slot_1` / `slot_2`).
- Menyesuaikan command `reminders:dispatch-urgent` untuk membaca jadwal dinamis dari database, memproses pengiriman dengan database `urgent_alert_logs` berbasis posisi slot untuk menjamin idempotensi di bawah perubahan jam, serta menyusun tanda slot dinamis (`Pagi I` / `Sore II`) pada pesan.
- Memperbarui `SettingController` dengan aturan validasi jam pertama dan kedua (jam kedua harus lebih malam dari pertama, jarak minimal 1 jam), menyimpan setelan baru ke database `settings`, serta mencatat riwayat perubahan ke log audit `settings.urgent_alert_times_updated`.
- Memperbarui antarmuka UI halaman Pengaturan Sistem untuk menampilkan input tipe `time` bagi Pengiriman Pertama dan Kedua lengkap dengan teks pembeda yang jelas bagi pengguna.
- **Koreksi Penghapusan Lisensi & Riwayat**: Mengubah fungsionalitas tombol Hapus Lisensi pada `LicenseController@destroy` menjadi soft delete (`$license->delete()`) alih-alih hard/force delete, dan membatalkan penghapusan baris riwayat `whatsapp_messages`. Memperbarui relasi model `LicenseContact`, `ReminderLog`, dan `UrgentAlertLog` untuk mendukung `withTrashed()`, serta menyesuaikan visualisasi riwayat (`reminders/index.blade.php`) dengan label merah `(Terhapus)` tanpa link 404 ketika lisensi terkait telah dihapus.
- **Fitur Baru (Approval & Siklus Hidup Lisensi)**:
  - Membuat migrasi database baru untuk menambahkan kolom `lifecycle_status` (`'active'` / `'stopped'`), `stopped_at`, dan `stopped_by` pada tabel `licenses`.
  - Menambahkan migrasi database baru untuk menambahkan status `'skipped'` pada kolom `status` tabel `urgent_alert_logs`.
  - Menyediakan form perpanjangan masa aktif (Renew) di detail lisensi yang memperbarui start/end date, meregenerasi antrean reminder untuk masa aktif baru, dan mengubah status ke `active` kembali.
  - Menyediakan tombol "Hentikan" (Stop) dengan konfirmasi SweetAlert2 yang mengubah status lisensi menjadi `stopped`, menyimpan metadata pemutus (`stopped_at`, `stopped_by`), dan secara instan menandai sisa reminder pending reguler dan urgent alert H-0 sebagai `skipped`.
  - Menyediakan tombol "Aktifkan Kembali" (Reactivate) untuk lisensi yang diarsip dengan input periode tanggal baru.
  - Mengecualikan lisensi berstatus `stopped` dari dispatches di `reminders:dispatch` dan `reminders:dispatch-urgent`.
  - Menyembunyikan lisensi `stopped` dari dashboard utama, list lisensi aktif default, KPI, dan distribusi status.
  - Menambahkan filter pencarian tab (Aktif / Dihentikan / Semua) pada halaman daftar lisensi dengan badge status visual abu-abu "Dihentikan".
  - Menampilkan banner pembuat keputusan di halaman detail lisensi jika status aktif telah expired, dan banner info arsip jika statusnya `stopped`.
  - Mencatat aksi-aksi ini ke log audit: `license.renewed`, `license.stopped`, dan `license.reactivated`.
- **Verifikasi Logika Tombol Aksi**: Memastikan tombol "Hentikan" dan "Perpanjang" diatur murni berdasarkan `lifecycle_status` (aktif saat `'active'`, tersembunyi saat `'stopped'`), tidak dipengaruhi oleh status kesehatan lisensi (Aman, Waspada, Kritis, maupun Expired). Tombol "Kirim reminder sekarang" juga dipastikan tetap aktif untuk seluruh lisensi `'active'` termasuk lisensi yang sudah Expired.
- **Penghapusan Penanda Slot H-0**: Menghapus kalimat penanda dinamis `*(Pengingat Pagi I - Hari Terakhir)*` dan `*(Pengingat Sore II - Hari Terakhir)*` dari isi pesan pengingat hari-H (H-0) agar pesan tampil lebih bersih tanpa embel-embel slot.

### Capaian
- Fitur jam pengiriman hari-H dinamis selesai dibuat dengan mekanisme idempotensi berbasis slot yang aman, serta visualisasi data riwayat pengingat H-0 otomatis tampil konsisten dan tepat. Pengiriman pesan ganda (3 pesan di hari-H) berhasil dicegah total, dan timeline detail lisensi menyajikan visualisasi riil kedua slot pengiriman secara kronologis. Riwayat pengingat dan statistik jumlah pesan keluar harian kini tetap utuh meskipun data lisensi terkait dihapus oleh pengguna.
- Fitur persetujuan siklus hidup (Renew / Stop / Reactivate) terintegrasi dengan sempurna. Tombol-tombol kontrol siklus hidup lisensi (Hentikan & Perpanjang) tervalidasi aktif penuh pada seluruh kondisi kesehatan termasuk lisensi Expired (yang status daur hidupnya masih aktif), sementara tombol manual "Kirim reminder sekarang" terbukti hanya dinonaktifkan jika lisensi dihentikan (`'stopped'`).

---

## Ringkasan Capaian 13 Juli – 12 Agustus 2026

- **Minggu 1 (13–17 Juli)**: fondasi Laravel + Tailwind, skema database utama, controller/model utama, sistem berkas privat, mesin scheduling otomatis, integrasi Meta WhatsApp API / Log Gateway, panduan deployment, dan polesan logo sidebar.
- **Minggu 2 (20–24 Juli)**: implementasi Airy Design, optimasi contrast warna dashboard, perbaikan responsive view mobile, penggabungan filter status, integrasi Fonnte gateway via UI, fitur edit template pesan dinamis, penutupan register publik dengan undangan admin, verifikasi login OTP, dan kustomisasi bg login page.
- **Minggu 3 (27 Juli–5 Agustus)**: pembuatan dropdown select kustom `<x-custom-select>`, penyelarasan tombol aksi di seluruh sistem, integrasi SweetAlert2, penanganan bug notifikasi stuck, perbaikan ikon Kelola Admin yang tidak tampil, dan penyusunan logbook harian.
- **Minggu 4 (6–12 Agustus)**: Fitur template kustomisasi pesan setelah kedaluwarsa (overdue), pemisahan tab template di Pengaturan, serta penambahan input jam alert hari-H kustom di panel Pengaturan lengkap dengan migrasi data historis berbasis posisi slot.
