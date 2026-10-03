<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class FormatTanggalIndonesia
{
    /**
     * @var array<int, string>
     */
    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /**
     * @var array<int, string>
     */
    private const NAMA_HARI = [
        0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
        4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
    ];

    public static function tanggalBulanTahun(?CarbonInterface $tanggal): string
    {
        if ($tanggal === null) {
            return '—';
        }

        return sprintf(
            '%d %s %d',
            $tanggal->day,
            self::NAMA_BULAN[(int) $tanggal->month],
            $tanggal->year
        );
    }

    public static function hariIniLengkap(?CarbonInterface $tanggal): string
    {
        if ($tanggal === null) {
            return '—';
        }

        return sprintf(
            '%s tanggal %d bulan %s tahun %d',
            self::NAMA_HARI[(int) $tanggal->dayOfWeek],
            $tanggal->day,
            self::NAMA_BULAN[(int) $tanggal->month],
            $tanggal->year
        );
    }
}
