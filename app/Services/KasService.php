<?php

namespace App\Services;

use App\Models\KasSaldoAwal;
use App\Models\KasTransaksi;
use Illuminate\Database\Eloquent\Builder;

class KasService
{
    public function saldoAwal(int $kelurahanId, ?int $pokjaId, int $tahun, string $pos): float
    {
        $query = KasSaldoAwal::query()
            ->where('kelurahan_id', $kelurahanId)
            ->where('tahun', $tahun)
            ->where('pos', $pos);

        if ($pokjaId === null) {
            $query->whereNull('pokja_id');
        } else {
            $query->where('pokja_id', $pokjaId);
        }

        $row = $query->first();

        return $row !== null ? (float) $row->jumlah : 0.0;
    }

    public function totalMasuk(int $kelurahanId, ?int $pokjaId, int $tahun, string $pos): float
    {
        return (float) $this->transaksiQuery($kelurahanId, $pokjaId, $tahun, $pos)
            ->where('jenis', KasTransaksi::JENIS_MASUK)
            ->sum('jumlah');
    }

    public function totalKeluar(int $kelurahanId, ?int $pokjaId, int $tahun, string $pos): float
    {
        return (float) $this->transaksiQuery($kelurahanId, $pokjaId, $tahun, $pos)
            ->where('jenis', KasTransaksi::JENIS_KELUAR)
            ->sum('jumlah');
    }

    public function saldoAkhir(int $kelurahanId, ?int $pokjaId, int $tahun, string $pos): float
    {
        return $this->saldoAwal($kelurahanId, $pokjaId, $tahun, $pos)
            + $this->totalMasuk($kelurahanId, $pokjaId, $tahun, $pos)
            - $this->totalKeluar($kelurahanId, $pokjaId, $tahun, $pos);
    }

    /**
     * @return array{
     *     tunai: array{saldo_awal: float, masuk: float, keluar: float, saldo_akhir: float},
     *     bank: array{saldo_awal: float, masuk: float, keluar: float, saldo_akhir: float},
     *     total: array{saldo_awal: float, masuk: float, keluar: float, saldo_akhir: float}
     * }
     */
    public function ringkasan(int $kelurahanId, ?int $pokjaId, int $tahun): array
    {
        $tunai = $this->ringkasanPos($kelurahanId, $pokjaId, $tahun, KasTransaksi::POS_TUNAI);
        $bank = $this->ringkasanPos($kelurahanId, $pokjaId, $tahun, KasTransaksi::POS_BANK);

        return [
            'tunai' => $tunai,
            'bank' => $bank,
            'total' => [
                'saldo_awal' => $tunai['saldo_awal'] + $bank['saldo_awal'],
                'masuk' => $tunai['masuk'] + $bank['masuk'],
                'keluar' => $tunai['keluar'] + $bank['keluar'],
                'saldo_akhir' => $tunai['saldo_akhir'] + $bank['saldo_akhir'],
            ],
        ];
    }

    /**
     * @return list<array{bulan: int, nama_bulan: string, masuk: float, keluar: float, saldo_akhir: float}>
     */
    public function rekapBulanan(int $kelurahanId, ?int $pokjaId, int $tahun, ?string $pos = null): array
    {
        if ($pos !== null) {
            $saldoBerjalan = $this->saldoAwal($kelurahanId, $pokjaId, $tahun, $pos);
        } else {
            $saldoBerjalan = $this->saldoAwal($kelurahanId, $pokjaId, $tahun, KasTransaksi::POS_TUNAI)
                + $this->saldoAwal($kelurahanId, $pokjaId, $tahun, KasTransaksi::POS_BANK);
        }

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $hasil = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            if ($pos !== null) {
                $masuk = $this->jumlahBulan($kelurahanId, $pokjaId, $tahun, $pos, $bulan, KasTransaksi::JENIS_MASUK);
                $keluar = $this->jumlahBulan($kelurahanId, $pokjaId, $tahun, $pos, $bulan, KasTransaksi::JENIS_KELUAR);
            } else {
                $masuk = $this->jumlahBulan($kelurahanId, $pokjaId, $tahun, KasTransaksi::POS_TUNAI, $bulan, KasTransaksi::JENIS_MASUK)
                    + $this->jumlahBulan($kelurahanId, $pokjaId, $tahun, KasTransaksi::POS_BANK, $bulan, KasTransaksi::JENIS_MASUK);
                $keluar = $this->jumlahBulan($kelurahanId, $pokjaId, $tahun, KasTransaksi::POS_TUNAI, $bulan, KasTransaksi::JENIS_KELUAR)
                    + $this->jumlahBulan($kelurahanId, $pokjaId, $tahun, KasTransaksi::POS_BANK, $bulan, KasTransaksi::JENIS_KELUAR);
            }

            $saldoBerjalan = $saldoBerjalan + $masuk - $keluar;

            $hasil[] = [
                'bulan' => $bulan,
                'nama_bulan' => $namaBulan[$bulan],
                'masuk' => $masuk,
                'keluar' => $keluar,
                'saldo_akhir' => $saldoBerjalan,
            ];
        }

        return $hasil;
    }

    private function jumlahBulan(
        int $kelurahanId,
        ?int $pokjaId,
        int $tahun,
        string $pos,
        int $bulan,
        string $jenis
    ): float {
        return (float) $this->transaksiQuery($kelurahanId, $pokjaId, $tahun, $pos)
            ->whereMonth('tanggal', $bulan)
            ->where('jenis', $jenis)
            ->sum('jumlah');
    }

    /**
     * @return array{saldo_awal: float, masuk: float, keluar: float, saldo_akhir: float}
     */
    private function ringkasanPos(int $kelurahanId, ?int $pokjaId, int $tahun, string $pos): array
    {
        $saldoAwal = $this->saldoAwal($kelurahanId, $pokjaId, $tahun, $pos);
        $masuk = $this->totalMasuk($kelurahanId, $pokjaId, $tahun, $pos);
        $keluar = $this->totalKeluar($kelurahanId, $pokjaId, $tahun, $pos);

        return [
            'saldo_awal' => $saldoAwal,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'saldo_akhir' => $saldoAwal + $masuk - $keluar,
        ];
    }

    /**
     * @return Builder<KasTransaksi>
     */
    private function transaksiQuery(int $kelurahanId, ?int $pokjaId, int $tahun, string $pos)
    {
        $query = KasTransaksi::query()
            ->where('kelurahan_id', $kelurahanId)
            ->where('tahun', $tahun)
            ->where('pos', $pos);

        if ($pokjaId === null) {
            $query->whereNull('pokja_id');
        } else {
            $query->where('pokja_id', $pokjaId);
        }

        return $query;
    }
}
