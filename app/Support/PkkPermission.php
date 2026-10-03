<?php

namespace App\Support;

final class PkkPermission
{
    public const KELOLA_PENGGUNA = 'kelola_pengguna';

    public const KELOLA_ANGGOTA = 'kelola_anggota';

    public const KELOLA_SURAT = 'kelola_surat';

    public const KELOLA_KEGIATAN = 'kelola_kegiatan';

    public const ISI_PRESENSI = 'isi_presensi';

    public const KELOLA_BUKU_TAMU = 'kelola_buku_tamu';

    public const KELOLA_BUKU_KUNJUNGAN = 'kelola_buku_kunjungan';

    public const LIHAT_BUKU = 'lihat_buku';

    public const VERIFIKASI_BUKU = 'verifikasi_buku';

    public const LIHAT_AUDIT = 'lihat_audit';

    public const KELOLA_KAS = 'kelola_kas';

    public const LIHAT_KAS = 'lihat_kas';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::KELOLA_PENGGUNA,
            self::KELOLA_ANGGOTA,
            self::KELOLA_SURAT,
            self::KELOLA_KEGIATAN,
            self::ISI_PRESENSI,
            self::KELOLA_BUKU_TAMU,
            self::KELOLA_BUKU_KUNJUNGAN,
            self::LIHAT_BUKU,
            self::VERIFIKASI_BUKU,
            self::LIHAT_AUDIT,
            self::KELOLA_KAS,
            self::LIHAT_KAS,
        ];
    }

    public static function middleware(string ...$permissions): string
    {
        return 'permission:'.implode('|', $permissions);
    }
}
