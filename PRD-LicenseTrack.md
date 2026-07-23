# PRD — LicenseTrack

**Sistem Manajemen & Pengingat Kedaluwarsa Lisensi**
Project Magang — PT Hariff
Versi 1.0

---

## 1. Ringkasan

LicenseTrack adalah aplikasi web internal untuk mencatat seluruh lisensi/sertifikasi/key perusahaan beserta file pendukungnya, memantau masa berlakunya, dan **secara otomatis mengirim pengingat WhatsApp** ke PIC pemilik lisensi menjelang tanggal kedaluwarsa.

**Masalah yang diselesaikan:** lisensi sering lewat masa berlaku karena tidak ada yang memantau secara terpusat, dan pengingat manual mudah terlupakan.

---

## 2. Tujuan & Non-Tujuan

### Tujuan
- Satu tempat terpusat untuk menyimpan data + file lisensi.
- Pengingat WhatsApp otomatis, terjadwal, dan **tidak duplikat**.
- Dashboard monitoring status semua lisensi (aktif / mendekati expired / expired).
- Riwayat pengiriman yang bisa diaudit (kapan dikirim, ke siapa, berhasil/gagal).

### Non-Tujuan (Out of Scope v1)
- Multi-tenant / multi-perusahaan.
- Proses approval perpanjangan lisensi.
- Reminder via email atau SMS.
- Balasan/percakapan dua arah via WhatsApp (chatbot).
- Role selain Admin.

---

## 3. Keputusan Teknis

| Aspek | Keputusan |
|---|---|
| Framework | Laravel 11 (Blade + Tailwind) |
| Database | MySQL |
| Queue | `database` driver |
| Scheduler | Laravel Scheduler (`schedule:work` saat lokal) |
| Gateway WA | **Meta WhatsApp Cloud API (resmi)** |
| Deployment | Lokal dulu (demo & laporan magang) |
| Role | Admin saja |
| Timezone | `Asia/Jakarta` |

### Aturan Pengembangan (wajib)
- **JANGAN membuat automated testing** (unit/feature test). Verifikasi manual saja.
- **Update `README.md` setiap kali ada perubahan logika.**
- Semua integrasi WhatsApp dibungkus di balik interface `WhatsAppGateway`, dengan implementasi:
  - `MetaCloudGateway` — implementasi asli.
  - `LogGateway` — dummy untuk development (pesan hanya ditulis ke log, tidak benar-benar dikirim).
  Dipilih lewat `.env` (`WA_GATEWAY=log|meta`).

---

## 4. Aktor

| Aktor | Deskripsi |
|---|---|
| **Admin** | Satu-satunya user yang login. Input & kelola lisensi, lihat dashboard, kelola template pesan, kirim reminder manual. |
| **PIC Lisensi** | Penerima pesan WhatsApp. **Tidak punya akun / tidak login.** Hanya tercatat nama + nomor WA-nya. |

---

## 5. Fitur & User Story

### F-01 — Autentikasi Admin
- Login dengan email + password (Laravel Breeze).
- Tidak ada halaman registrasi publik. Akun dibuat lewat seeder.

### F-02 — CRUD Lisensi
Admin dapat menambah lisensi dengan field:

| Field | Tipe | Wajib | Catatan |
|---|---|---|---|
| Nama lisensi | text | ✅ | mis. "Antivirus Kaspersky Endpoint" |
| Vendor / penerbit | text | — | |
| Deskripsi | textarea | — | |
| License key / nomor sertifikat | text | — | disimpan **terenkripsi** |
| Tanggal mulai berlaku | date | ✅ | |
| Tanggal berakhir | date | ✅ | harus > tanggal mulai |
| Status | enum | ✅ | `active` / `renewed` / `cancelled` |
| File lampiran | file[] | — | multi-file, lihat F-03 |
| Kontak PIC | repeatable | ✅ min 1 | nama + no. WhatsApp, lihat F-04 |

- Edit & Hapus (soft delete).
- **Penting:** jika `tanggal berakhir` diubah saat edit → jadwal reminder **di-generate ulang** (lihat F-06).

### F-03 — Upload File Lisensi
- Multi-file per lisensi (PDF, JPG, PNG, DOCX, ZIP). Maks 10 MB/file.
- Disimpan di **private disk** (`storage/app/licenses/`), **bukan** `storage/app/public`.
- Download hanya lewat route terautentikasi (`Storage::download()`), bukan URL langsung.
- Alasan: sertifikat & key adalah data sensitif.

### F-04 — Kontak PIC
- Satu lisensi bisa punya lebih dari satu PIC (mis. teknis + manajerial).
- Satu PIC ditandai `is_primary`.
- Nomor WA **dinormalisasi otomatis** ke format `628xxxxxxxxx` sebelum disimpan:
  - `08123...` → `628123...`
  - `+62 812-3456` → `628123456`
  - Buang spasi, strip, tanda kurung.
  - Validasi: hanya digit, panjang 10–15.

### F-05 — Dashboard Monitoring
Halaman utama berisi:
- **KPI Cards:** Total Lisensi · Akan Expired ≤30 Hari · Akan Expired ≤90 Hari · Sudah Expired.
- **Tabel lisensi** dengan kolom: Nama, Vendor, PIC, Tgl Berakhir, **Sisa Hari**, Status Reminder Terakhir.
  - Badge warna: 🟢 aman (>90 hari) · 🟡 waspada (31–90) · 🟠 kritis (1–30) · 🔴 expired.
- Filter: status, rentang tanggal berakhir, vendor. Search by nama.
- Sort default: tanggal berakhir ascending (yang paling mendesak di atas).

### F-06 — Reminder Engine ⭐ (inti sistem)

**Milestone pengingat** (dihitung dari `end_date`):

| Fase | Milestone (H-) |
|---|---|
| 3 bulan sebelum — 1x | `90` |
| 2 bulan sebelum — 2x (tiap 2 minggu) | `60`, `45` |
| 1 bulan sebelum — tiap minggu | `30`, `21`, `14`, `7` |
| Kritis | `1`, `0` |
| Terlambat (overdue) | `-7`, `-14` |

Disimpan sebagai konstanta: `config('reminder.milestones')`.

**Mekanisme: pre-generate, bukan hitung on-the-fly.**

Saat lisensi **dibuat** atau `end_date`-nya **diubah**:
1. Hapus semua `reminder_logs` milik lisensi tsb yang statusnya masih `pending`.
2. Untuk setiap milestone `m`:
   - `scheduled_at = end_date - m hari`, di jam kirim (default 09:00 WIB).
   - Jika `scheduled_at` **sudah lewat** → simpan dengan status `skipped`.
     *(Ini mencegah masalah "catch-up": lisensi yang diinput saat sisa 10 hari tidak akan langsung memuntahkan 6 pesan sekaligus.)*
   - Jika belum lewat → simpan dengan status `pending`.

**Idempotensi:** tabel `reminder_logs` punya `UNIQUE(license_id, milestone)`. Command boleh dijalankan berulang kali dalam sehari tanpa risiko mengirim pesan yang sama dua kali.

**Command:** `php artisan reminders:dispatch`
```
1. Ambil reminder_logs WHERE status = 'pending' AND scheduled_at <= now()
2. Skip jika lisensi sudah status 'renewed' atau 'cancelled' → tandai 'skipped'
3. Untuk tiap reminder → dispatch job SendWhatsAppReminder ke queue
4. Tandai status 'queued'
```
Dijadwalkan `->everyFiveMinutes()` di `routes/console.php`.

**Job `SendWhatsAppReminder`:**
- Kirim ke **semua PIC** lisersi tsb (1 baris `reminder_log` → N pesan, dicatat di `whatsapp_messages`).
- `tries = 3`, `backoff = [60, 300, 900]` detik.
- Sukses → status `sent`, isi `sent_at` + `wamid`.
- Gagal setelah semua retry → status `failed` + simpan `error_message`.

**Manual override (penting untuk demo & kasus mendesak):**
- Tombol **"Kirim Reminder Sekarang"** di halaman detail lisensi.
- Tombol **"Proses Antrian"** di dashboard → menjalankan `reminders:dispatch` on-demand. Berguna karena di lokal tidak ada cron.
- Tombol **"Kirim Test"** di halaman Pengaturan → kirim ke nomor sendiri untuk memastikan gateway hidup.

### F-07 — Riwayat & Log Pengiriman
- Halaman **Riwayat Reminder**: tabel semua `whatsapp_messages` — Lisensi, PIC, Nomor, Milestone, Waktu Kirim, Status, Error.
- Filter by status (`sent` / `failed` / `pending`).
- Tombol **Retry** untuk yang `failed`.
- Di halaman detail lisensi: **timeline** jadwal reminder (yang sudah lewat, yang akan datang) — ini juga jadi bukti visual saat demo bahwa sistemnya bekerja.

### F-08 — Pengaturan
- Jam kirim reminder (default `09:00`).
- Nama pengirim / nama perusahaan (dipakai di isi pesan).
- Pilihan gateway aktif (`log` / `meta`).
- Preview isi pesan yang akan dikirim.

---

## 6. Data Model

```
users
  id, name, email, password, timestamps

licenses
  id
  name                 varchar
  vendor               varchar nullable
  description          text nullable
  license_key          text nullable      -- cast: 'encrypted'
  start_date           date
  end_date             date               -- INDEX
  status               enum('active','renewed','cancelled') default 'active'
  created_by           fk users
  timestamps, softDeletes

license_files
  id
  license_id           fk licenses cascade
  path                 varchar            -- private disk
  original_name        varchar
  mime_type            varchar
  size                 unsignedBigInteger
  timestamps

license_contacts
  id
  license_id           fk licenses cascade
  name                 varchar
  phone                varchar            -- sudah ternormalisasi: 628xxx
  is_primary           boolean default false
  timestamps

reminder_logs
  id
  license_id           fk licenses cascade
  milestone            integer            -- 90, 60, 45, 30, 21, 14, 7, 1, 0, -7, -14
  scheduled_at         datetime
  status               enum('pending','queued','sent','failed','skipped')
  sent_at              datetime nullable
  timestamps
  UNIQUE(license_id, milestone)
  INDEX(status, scheduled_at)

whatsapp_messages
  id
  reminder_log_id      fk reminder_logs nullable   -- null jika kiriman manual/test
  license_contact_id   fk license_contacts nullable
  phone                varchar
  body                 text                        -- isi pesan hasil render
  status               enum('pending','sent','failed')
  wamid                varchar nullable            -- message id dari Meta
  error_message        text nullable
  sent_at              datetime nullable
  timestamps

settings
  key                  varchar primary
  value                text
```

---

## 7. Integrasi Meta WhatsApp Cloud API

### 7.1 Prasyarat (dilakukan manual sekali)
1. Buat App di **Meta for Developers** → tambahkan produk **WhatsApp**.
2. Gunakan **Test Number** yang disediakan Meta (gratis).
   - ⚠️ Test number hanya bisa mengirim ke **maksimal 5 nomor** yang didaftarkan manual di dashboard. Cukup untuk demo magang.
3. Catat **Phone Number ID** dan **WABA ID**.
4. Buat **System User Access Token** (permanen). Token sementara hanya berlaku 24 jam — jangan dipakai.
5. Buat & submit **Message Template** kategori **UTILITY**, bahasa **Indonesian (id)**.

### 7.2 Message Template
**Nama:** `license_expiry_reminder`
**Kategori:** UTILITY
**Bahasa:** id

```
Halo {{1}},

Pengingat dari {{2}}.

Lisensi *{{3}}* akan berakhir pada *{{4}}* — tersisa {{5}} hari.

Mohon segera koordinasikan proses perpanjangannya. Terima kasih.
```

| Param | Isi |
|---|---|
| `{{1}}` | Nama PIC |
| `{{2}}` | Nama perusahaan (dari Settings) |
| `{{3}}` | Nama lisensi |
| `{{4}}` | Tanggal berakhir (format `d F Y`, mis. "20 Oktober 2026") |
| `{{5}}` | Sisa hari |

> Buat juga varian template terpisah untuk kondisi **overdue** (milestone negatif), karena kalimat "tersisa X hari" tidak masuk akal untuk lisensi yang sudah lewat. Contoh nama: `license_expired_alert`.

### 7.3 Request
```
POST https://graph.facebook.com/v21.0/{PHONE_NUMBER_ID}/messages
Authorization: Bearer {ACCESS_TOKEN}
Content-Type: application/json
```
```json
{
  "messaging_product": "whatsapp",
  "to": "628123456789",
  "type": "template",
  "template": {
    "name": "license_expiry_reminder",
    "language": { "code": "id" },
    "components": [
      {
        "type": "body",
        "parameters": [
          { "type": "text", "text": "Budi Santoso" },
          { "type": "text", "text": "PT Hariff" },
          { "type": "text", "text": "Antivirus Kaspersky Endpoint" },
          { "type": "text", "text": "20 Oktober 2026" },
          { "type": "text", "text": "30" }
        ]
      }
    ]
  }
}
```

**Response sukses** → ambil `messages[0].id` → simpan sebagai `wamid`.
**Response gagal** → simpan `error.message` + `error.code` ke `error_message`.

### 7.4 `.env`
```env
WA_GATEWAY=log                # log | meta
WA_API_VERSION=v21.0
WA_PHONE_NUMBER_ID=
WA_ACCESS_TOKEN=
WA_TEMPLATE_REMINDER=license_expiry_reminder
WA_TEMPLATE_EXPIRED=license_expired_alert
WA_TEMPLATE_LANG=id

REMINDER_SEND_TIME=09:00
```

### 7.5 Webhook (Fase 2 — opsional)
Status `delivered` / `read` hanya bisa diterima lewat webhook, dan webhook butuh URL publik HTTPS. Karena aplikasi jalan di lokal, **Fase 1 tidak mengimplementasikan webhook**. Status cukup `sent` (artinya: diterima oleh server Meta).
Jika ingin dicoba: gunakan **ngrok** untuk expose `POST /webhook/whatsapp` ke internet.

---

## 8. Halaman

| Route | Halaman |
|---|---|
| `/login` | Login admin |
| `/dashboard` | KPI + tabel lisensi + filter |
| `/licenses/create` | Form tambah lisensi (+ file, + PIC repeatable) |
| `/licenses/{id}` | Detail: data, file, PIC, **timeline reminder**, tombol kirim manual |
| `/licenses/{id}/edit` | Edit lisensi |
| `/reminders` | Riwayat pengiriman + filter + retry |
| `/settings` | Jam kirim, nama perusahaan, gateway, tombol Kirim Test |

---

## 9. Non-Functional

- **Timezone:** set `APP_TIMEZONE=Asia/Jakarta` di `config/app.php`. Semua perhitungan `diffInDays` pakai `Carbon::today()` dengan timezone tsb.
- **Keamanan file:** private disk + route terautentikasi. Tidak ada `php artisan storage:link` untuk folder lisensi.
- **Enkripsi:** `license_key` pakai Eloquent `encrypted` cast.
- **Rate limit:** batasi maksimal N pesan per menit di job (Cloud API punya limit tier). Untuk skala internal, tidak akan tersentuh, tapi tetap pasang `->throttle()` di queue.
- **Logging:** semua panggilan API WA di-log (request + response) ke channel log terpisah `storage/logs/whatsapp.log`.

---

## 10. Roadmap

### Fase 1 — Fondasi
- Setup Laravel + Breeze + Tailwind.
- Migration semua tabel + model + relasi.
- CRUD Lisensi + upload file + kontak PIC.
- Dashboard + KPI + filter.
- Gateway `LogGateway` saja (belum kirim beneran).

### Fase 2 — Reminder Engine
- `config/reminder.php` (milestones).
- Service `ReminderScheduler` → generate/regenerate `reminder_logs`.
- Command `reminders:dispatch` + registrasi scheduler.
- Job `SendWhatsAppReminder` + queue.
- Timeline reminder di halaman detail.
- Halaman Riwayat + retry.

### Fase 3 — Integrasi Meta Cloud API
- `MetaCloudGateway` implementasi nyata.
- Halaman Settings + tombol Kirim Test.
- Uji end-to-end ke nomor sendiri (didaftarkan di test number Meta).

### Fase 4 — Polish
- Export daftar lisensi ke Excel (PhpSpreadsheet).
- Empty state, loading state, konfirmasi hapus.
- Finalisasi `README.md`.

---

## 11. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Template ditolak Meta | Gunakan bahasa netral, kategori UTILITY, hindari kata promosi. Siapkan waktu 1–2 hari untuk approval. |
| Test number hanya 5 penerima | Cukup untuk demo. Untuk produksi, Hariff perlu daftarkan nomor bisnis asli + verifikasi. |
| Tidak ada cron di lokal | Tombol "Proses Antrian" manual + `php artisan schedule:work` saat demo. |
| Access token expired | Wajib pakai System User token (permanen), bukan token sementara 24 jam. |
| Lisensi diinput mepet deadline → spam | Sudah ditangani lewat status `skipped` saat generate jadwal. |

---

## 12. Definition of Done

- [ ] Admin bisa login, input lisensi lengkap dengan file + minimal 1 PIC.
- [ ] Dashboard menampilkan sisa hari & badge warna yang benar.
- [ ] Jadwal reminder ter-generate otomatis & terlihat di timeline detail lisensi.
- [ ] Mengubah `end_date` me-regenerate jadwal.
- [ ] `reminders:dispatch` mengirim pesan WhatsApp asli ke nomor test, dan **tidak duplikat** meskipun dijalankan 3x berturut-turut.
- [ ] Kegagalan kirim tercatat dengan pesan error dan bisa di-retry.
- [ ] `README.md` berisi cara setup, cara dapat kredensial Meta, dan cara menjalankan scheduler + queue.
