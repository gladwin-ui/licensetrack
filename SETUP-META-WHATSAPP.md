# Panduan Setup Meta WhatsApp Cloud API — LicenseTrack

Dokumen ini berisi langkah-langkah **di sisi Meta** (bukan di kode). Kerjakan berurutan.

> ⚠️ Meta sering mengubah tampilan dashboard-nya. Kalau nama menu sedikit berbeda dari yang tertulis di sini, cari opsi yang paling mendekati — alur besarnya tetap sama.

---

## Yang perlu disiapkan sebelum mulai

- Akun **Facebook** pribadi yang aktif.
- **Nomor WhatsApp aktif** di HP kamu (untuk menerima pesan test).
- Nomor WhatsApp pembimbing (opsional, untuk demo).

**Tidak perlu:** kartu kredit, nomor bisnis khusus, atau verifikasi perusahaan. Test number dari Meta gratis.

---

## Langkah 1 — Daftar Akun Developer

1. Buka **`developers.facebook.com`**
2. Klik **Get Started** (pojok kanan atas).
3. Login dengan akun Facebook.
4. Verifikasi akun: masukkan nomor HP → terima OTP → isi data diri.
5. Saat ditanya "peran kamu", pilih **Developer**.

✅ Selesai kalau kamu bisa masuk ke halaman **My Apps**.

---

## Langkah 2 — Buat App

1. Di halaman **My Apps** → klik **Create App**.
2. Isi **App name**.
   > 💡 **Beri nama yang jelas menandakan ini untuk testing**, misalnya `LicenseTrack_Test_Hariff`. Bot otomatis Meta cukup galak dan bisa men-suspend akun yang terlihat mencurigakan. Menyebut kata "Test" membantu.
3. Isi email kontak.
4. Saat ditanya **use case**, pilih opsi yang mengarah ke **WhatsApp** / **Business**.
5. Klik **Create App**. Mungkin diminta masukkan password Facebook lagi.

> ⚠️ **Jangan isi informasi billing/kartu kredit.** Untuk sandbox tidak diperlukan.

---

## Langkah 3 — Tambahkan Produk WhatsApp

App yang baru dibuat itu masih kosong. Kamu harus "memasang" produk WhatsApp ke dalamnya.

1. Di dashboard app, scroll ke daftar produk → cari **WhatsApp** → klik **Set Up**.
2. Kamu akan diminta menghubungkan **Business Portfolio**. Kalau belum punya, ikuti prompt untuk membuatnya saat itu juga.
   > Sama seperti nama app, beri nama portfolio yang menandakan ini lingkungan test.

**Yang otomatis dibuat Meta untuk kamu setelah langkah ini:**
- **WABA test** (WhatsApp Business Account) untuk kirim pesan test gratis.
- **Nomor bisnis test** — bisa mengirim ke **maksimal 5 nomor penerima**.
- Template **`hello_world`** yang sudah pre-approved.

---

## Langkah 4 — Catat Kredensial

Masuk ke menu kiri: **WhatsApp → API Setup**.

Catat 2 hal ini (nanti masuk ke `.env`):

| Item | Contoh | Dipakai untuk |
|---|---|---|
| **Phone Number ID** | `123456789012345` | `WA_PHONE_NUMBER_ID` |
| **WhatsApp Business Account ID** | `987654321098765` | Referensi (kelola template) |

> ⚠️ **Phone Number ID ≠ nomor telepon.** Ini ID numerik panjang, bukan `+62...`. Sering ketuker.

**Jangan** ambil access token dari sini dulu — token di halaman ini hanya bertahan 24 jam. Kita ambil yang permanen di Langkah 6.

---

## Langkah 5 — Daftarkan Nomor Penerima (Allowed List)

Test number **hanya bisa mengirim ke nomor yang sudah didaftarkan**. Maksimal 5.

1. Masih di **WhatsApp → API Setup**.
2. Di bagian **Send and receive messages**, klik kolom **To** → pilih **Manage phone number list**.
3. Masukkan nomor WhatsApp kamu (format internasional: `+62812...`).
4. Nomor tersebut akan **menerima kode konfirmasi lewat WhatsApp**. Masukkan kodenya untuk verifikasi.
5. Ulangi untuk nomor lain (mis. nomor pembimbing). Maksimal 5 total.

> 💡 Untuk demo magang, cukup daftarkan: **nomormu sendiri** + **nomor pembimbing**. Sisakan slot kosong untuk jaga-jaga.

---

## Langkah 6 — Buat Access Token Permanen (System User)

**Ini langkah yang paling sering di-skip orang dan paling sering bikin masalah.** Token dari tombol "Generate access token" di API Setup **hanya hidup 24 jam**. Kalau kamu pakai itu, besok pagi aplikasimu error 401.

1. Buka **Meta Business Settings** (`business.facebook.com/settings`).
2. Menu kiri → **Users** → **System users**.
3. Klik **Add** → beri nama (mis. `licensetrack-system-user`) → role: **Admin** → Create.
4. Pilih system user yang baru dibuat → klik **Add Assets**:
   - Pilih **Apps** → pilih app kamu → aktifkan **Full control** / **Manage app**.
   - Pilih **WhatsApp Accounts** → pilih WABA kamu → aktifkan **Full control**.
5. Klik **Generate New Token**:
   - Pilih **App**: app LicenseTrack kamu.
   - **Token expiration**: pilih **Never**.
   - Centang permission:
     - ✅ `whatsapp_business_messaging`
     - ✅ `whatsapp_business_management`
6. Klik Generate → **SALIN TOKENNYA SEKARANG**.

> 🔴 Token ini **hanya ditampilkan satu kali**. Kalau tertutup, kamu harus generate ulang.
>
> 🔴 **Jangan pernah commit token ini ke GitHub.** Repo kamu publik. Token bocor = orang lain bisa kirim WhatsApp atas nama akunmu. Simpan hanya di `.env` (yang sudah masuk `.gitignore`).

---

## Langkah 7 — Buat Message Template

Ini yang paling makan waktu karena butuh review Meta.

1. Buka **WhatsApp Manager** (dari Meta Business Suite → WhatsApp Accounts → pilih WABA → WhatsApp Manager).
2. Masuk ke **Account tools → Message templates** → klik **Create template**.

### Template 1: Pengingat (belum expired)

| Field | Isi |
|---|---|
| **Category** | **Utility** |
| **Name** | `license_expiry_reminder` |
| **Language** | **Indonesian (id)** |

**Body:**
```
Halo {{1}},

Pengingat dari {{2}}.

Lisensi *{{3}}* akan berakhir pada *{{4}}* — tersisa {{5}} hari.

Mohon segera koordinasikan proses perpanjangannya. Terima kasih.
```

**Sample values** (wajib diisi, Meta pakai ini untuk review):
- `{{1}}` = `Budi Santoso`
- `{{2}}` = `PT Hariff`
- `{{3}}` = `Antivirus Kaspersky Endpoint`
- `{{4}}` = `20 Oktober 2026`
- `{{5}}` = `30`

**Header, Footer, Buttons:** kosongkan semua. Tidak perlu.

### Template 2: Sudah Lewat Masa Berlaku

| Field | Isi |
|---|---|
| **Category** | **Utility** |
| **Name** | `license_expired_alert` |
| **Language** | **Indonesian (id)** |

**Body:**
```
Halo {{1}},

Pemberitahuan dari {{2}}.

Lisensi *{{3}}* telah berakhir pada *{{4}}* dan sudah melewati masa berlaku selama {{5}} hari.

Mohon segera ditindaklanjuti. Terima kasih.
```

**Sample values:** sama seperti di atas, kecuali `{{5}}` = `7`.

3. Klik **Submit**.

### Aturan penting saat membuat template

- **Nama template**: huruf kecil, angka, dan underscore saja. `license_expiry_reminder` ✅ — `License Expiry Reminder` ❌
- **Ketik langsung di editor Meta.** Jangan copy-paste dari Word/Notion/Google Docs — bisa membawa formatting tersembunyi yang membuat template rusak.
- **Kategori harus UTILITY.** Kalau pilih Marketing, biayanya lebih mahal dan aturannya lebih ketat.
- **Jangan pakai kata berbau promosi** ("penawaran", "diskon", "buruan", "terbatas"). Meta punya algoritma yang bisa **mengubah paksa** kategori Utility jadi Marketing kalau mendeteksi bahasa promosi.
- Review biasanya **beberapa menit sampai 24 jam**. Status bisa dilihat di halaman Message templates.

### Kalau ditolak

Alasan penolakan paling umum: bahasa promosi di template Utility, typo/salah format, minta data sensitif, atau isi pesan yang tidak jelas maksudnya. Perbaiki dan submit ulang — tidak ada penalti untuk resubmit.

---

## Langkah 8 — Uji Manual SEBELUM Menyentuh Kode

**Ini penting. Jangan lewati.**

1. Kembali ke **WhatsApp → API Setup**.
2. Pastikan **From** = nomor test kamu, **To** = nomormu sendiri.
3. Klik **Send message** (yang mengirim template `hello_world`).
4. Cek HP kamu.

✅ **Kalau pesan masuk** → kredensial dan setup kamu benar. Silakan lanjut ke kode.
❌ **Kalau tidak masuk** → masalahnya ada di sisi Meta, bukan di Laravel. Selesaikan dulu di sini.

Prinsipnya: kalau nanti ada error, kamu jadi tahu pasti bahwa penyebabnya di kode — bukan menebak-nebak antara kode dan setup Meta. Ini menghemat berjam-jam debugging.

---

## Langkah 9 — Isi `.env` Laravel

```env
WA_GATEWAY=meta
WA_API_VERSION=v21.0
WA_PHONE_NUMBER_ID=123456789012345
WA_ACCESS_TOKEN=EAAxxxxxxxxxxxxxxxxx
WA_TEMPLATE_REMINDER=license_expiry_reminder
WA_TEMPLATE_EXPIRED=license_expired_alert
WA_TEMPLATE_LANG=id
```

Lalu buka halaman **Pengaturan** di LicenseTrack → **Kirim Test** → masukkan nomormu.

---

## Troubleshooting

| Gejala | Penyebab paling mungkin |
|---|---|
| Error `190` | Token invalid/expired. Kamu pakai token 24 jam, bukan System User token. Ulangi Langkah 6. |
| Error `131030` | Nomor tujuan belum ada di allowed list. Ulangi Langkah 5. |
| Error `132001` | Template belum di-approve, atau nama/bahasa template salah. Cek status di WhatsApp Manager. |
| Error `132000` | Jumlah parameter tidak cocok. Template punya 5 variabel, kode kamu kirim 4 (atau sebaliknya). |
| Error `100` | Phone Number ID salah — kemungkinan kamu masukkan nomor telepon, bukan ID-nya. |
| Pesan terkirim tapi tanggalnya "20 October 2026" | Bug di kode (pakai `format()` bukan `translatedFormat()`), bukan masalah Meta. |

---

## Catatan untuk Produksi (kalau Hariff mau pakai beneran)

Yang berubah kalau naik dari test number ke nomor asli:

1. **Verifikasi bisnis Meta** — upload dokumen legal perusahaan (NIB/akta/NPWP). Prosesnya beberapa hari kerja. Tanpa ini, ada batasan jumlah percakapan per hari.
2. **Nomor telepon khusus** — dan ini krusial:

> 🔴 **Nomor yang didaftarkan ke Cloud API TIDAK BISA lagi dipakai di aplikasi WhatsApp biasa.** Nomor itu berpindah sepenuhnya ke API — tidak bisa dibuka di HP, tidak bisa chat manual.
>
> Artinya Hariff perlu menyiapkan **nomor baru/khusus** untuk sistem ini. Jangan pakai nomor WhatsApp pribadi siapa pun, dan jangan pakai nomor CS yang masih aktif dipakai chat.

3. **Metode pembayaran** — pesan Utility ke nomor Indonesia dikenakan biaya per percakapan. Perlu kartu/metode bayar terdaftar di Meta Billing.
