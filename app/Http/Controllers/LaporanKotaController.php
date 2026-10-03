<?php

namespace App\Http\Controllers;

use App\Services\LaporanKotaService;
use App\Support\ActiveKelurahan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanKotaController extends Controller
{
    public function __construct(
        private LaporanKotaService $laporanKotaService,
        private ActiveKelurahan $activeKelurahan,
    ) {}

    public function index(Request $request): View
    {
        $filter = $this->laporanKotaService->normalisasiFilter($request->only(['tahun', 'bulan']));
        $kelurahan = $this->activeKelurahan->resolve();
        $dataset = $this->laporanKotaService->dataset($filter, $kelurahan);

        return view('laporan-kota.index', [
            'dataset' => $dataset,
            'filters' => $filter,
            'kelurahan' => $kelurahan,
            'daftarKelurahan' => $this->activeKelurahan->daftarUntukPemilih(),
        ]);
    }
}
