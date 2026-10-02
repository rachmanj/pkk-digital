# Spec — Buku PKK Digital (MVP Fase 1)

> Turunan dari diskusi 02-10-2026 (grill-me) yang semua rekomendasinya disetujui Iwan.
> Rencana implementasi & task list: `.hermes/plans/2026-10-02_231325-buku-pkk-digital.md`

## 1. Goal

TP PKK Kelurahan mencatat administrasi sekali (surat, kegiatan, presensi, anggota), lalu semua buku wajib terisi otomatis dan bisa dicetak/disalin ke format buku standar (PDF + Excel). GSI jadi kelurahan pertama; skema siap multi-kelurahan.

## 2. Scope

**In (Fase 1):** master orang & keanggotaan (TP PKK, kader umum, kader khusus), agenda surat masuk/keluar + disposisi ke Pokja + scan lampiran, kegiatan + presensi + notulen, buku tamu, buku kunjungan, cetak PDF & export Excel per buku, import data dari `Buku PKK GSI new.xlsx`, role/permission, audit log.

**Out (fase berikutnya):** keuangan/kas & buku tabungan (Fase 2), inventaris (Fase 3), matriks program kerja & realisasi (Fase 4), generator laporan program unggulan + dashboard Kota (Fase 5), absen mandiri kader via QR, tanda tangan digital.

## 3. Tech decisions

- Laravel 11/12, Blade + AdminLTE — konsisten dengan app Iwan yang lain, paling murah dirawat.
- MySQL 8; **dev**: DB `pkk_digital_dev` di dea-geekom (JANGAN DB live).
- spatie/laravel-permission (role), spatie/laravel-activitylog (jejak audit), maatwebsite/excel (export), dompdf (cetak).
- UI Bahasa Indonesia, angka ringkas, tanpa chart berlebihan, layout cetak terpisah dari layout layar.
- Urutan kolom tiap buku disimpan di `config/buku/*.php` supaya perubahan format (saat pembinaan) tidak menyentuh logika.
- Deploy: Docker (app + mysql + nginx), backup DB terjadwal sebelum migrasi.

## 4. DB changes

Lihat §2 rencana. Ringkas: `kelurahan`, `pokja`, `rt`, `orang`, `keanggotaan`, `agenda_surat`, `disposisi`, `kegiatan`, `presensi`, `buku_tamu`, `buku_kunjungan`, `notulen`, + tabel spatie. Semua ber-`kelurahan_id`; nomor urut buku = `no_urut_tahun` yang di-assign saat insert (stabil, tidak dihitung ulang).

Tidak ada tabel untuk BUKU KEGIATAN dan DAFTAR HADIR — keduanya view cetak dari `kegiatan` + `presensi`.

## 5. UI/UX — halaman Fase 1

| Halaman | Isi utama |
|---|---|
| Dashboard | kegiatan terdekat, surat belum ditindaklanjuti, jumlah anggota/kader, shortcut input |
| Anggota & kader | tabel + filter Pokja/jenis kader/RT, form orang + keanggotaan (foto opsional) |
| Agenda surat | dua tab (masuk/keluar), tombol disposisi, kolom status tindak lanjut, badge terlambat |
| Kegiatan | daftar + form; di dalam kegiatan: input presensi cepat (cari nama master atau nama manual), tombol buat notulen |
| Buku tamu / Buku kunjungan | CRUD ringkas + export |
| Cetak | dropdown pilih buku + periode → PDF siap TTD; tombol Export Excel di tiap halaman buku |
| Pengaturan | profil kelurahan (kop), Pokja, RT/dasawisma, user & role |

Aturan UI: form wajib tahan gagal submit (jangan hilang isian), aksi simpan selalu menampilkan nomor urut yang diberikan, dan halaman cetak memakai kolom PERSIS seperti file asli.

## 6. API / routes (Fase 1)

- `GET/POST /orang`, `PUT/DELETE /orang/{id}` — master orang & keanggotaan
- `GET/POST /agenda-surat`, `PUT/DELETE /agenda-surat/{id}`, `POST /agenda-surat/{id}/disposisi`, `PUT /disposisi/{id}`
- `GET/POST /kegiatan`, `PUT/DELETE /kegiatan/{id}`, `POST /kegiatan/{id}/presensi`, `POST /kegiatan/{id}/notulen`
- `GET/POST /buku-tamu`, `GET/POST /buku-kunjungan`
- `GET /cetak/{buku}?dari=&sampai=` (HTML print / PDF), `GET /export/{buku}?dari=&sampai=` (xlsx)
- `POST /import` (atau CLI `php artisan pkk:import-buku-file`) — hanya untuk superadmin/sekretaris
- Semua route di belakang `auth` + middleware role/permission.

## 7. Risks

- Data pribadi (alamat, tanggal lahir, no HP, foto) → akses ber-role, foto lewat route terautentikasi, tanpa halaman publik.
- Format buku bisa berubah saat pembinaan → konfigurasi kolom terpisah dari kode.
- Nomor urut harus stabil → assign saat insert, tidak dihitung ulang setelah penghapusan.
- Import data lama bisa kotor (nomor dobel, format tanggal campur "2026-02-18" vs "28-Juni-26", sel gabungan) → baris ragu dilewati + dilaporkan, tidak dikarang.
- Koneksi internet kelurahan tidak selalu bagus → UI ringan, export sebagai salinan lokal, backup harian.

## 8. Masih menunggu jawaban

1. Hosting & domain (dea-geekom + tunnel vs VPS) dan siapa yang mengakses dari luar.
2. Operator harian: sekretaris atau kader RT.
3. Ada/tidak aplikasi & format resmi dari PKK Kota/Kecamatan (acuan "Griya Digitalisasi").
4. Absen mandiri kader via HP di Fase 1 atau cukup operator.
5. Cakupan import: hanya data 2026 atau termasuk 2022–2025.
