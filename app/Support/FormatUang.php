<?php

namespace App\Support;

final class FormatUang
{
    public static function rupiah(float|string|null $jumlah): string
    {
        if ($jumlah === null || $jumlah === '') {
            return 'Rp 0';
        }

        $nilai = (float) $jumlah;

        return 'Rp '.number_format($nilai, 0, ',', '.');
    }
}
