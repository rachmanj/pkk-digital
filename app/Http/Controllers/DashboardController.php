<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveKelurahan;
use App\Models\AgendaSurat;
use App\Models\InventarisBarang;
use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\ProgramKerja;
use App\Models\User;
use App\Services\KasService;
use App\Support\ActiveKelurahan;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesActiveKelurahan;

    public function __construct(
        private KasService $kasService,
        private ActiveKelurahan $activeKelurahan,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $kelurahan = $this->activeKelurahan();

        $pokja = $kelurahan?->pokja()->orderBy('kode')->get() ?? collect();
        $pokjaCount = $pokja->count();

        $ringkasanLintas = null;
        if ($user instanceof User && $user->hasRole('superadmin')) {
            $ringkasanLintas = $this->ringkasanLintasKelurahan((int) now()->year);
        }

        return view('dashboard', [
            'kelurahan' => $kelurahan,
            'pokja' => $pokja,
            'pokjaCount' => $pokjaCount,
            'ringkasanLintas' => $ringkasanLintas,
            'daftarKelurahan' => $user instanceof User && $user->hasRole('superadmin')
                ? $this->activeKelurahan->daftarUntukPemilih()
                : collect(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function ringkasanLintasKelurahan(int $tahun): Collection
    {
        return Kelurahan::query()->orderBy('nama')->get()->map(function (Kelurahan $kelurahan) use ($tahun) {
            $kas = $this->kasService->ringkasan($kelurahan->id, null, $tahun);

            return [
                'kelurahan' => $kelurahan,
                'jumlah_orang' => Orang::query()->where('kelurahan_id', $kelurahan->id)->count(),
                'jumlah_surat' => AgendaSurat::query()->where('kelurahan_id', $kelurahan->id)->count(),
                'jumlah_kegiatan' => Kegiatan::query()->where('kelurahan_id', $kelurahan->id)->count(),
                'saldo_kas_akhir' => $kas['total']['saldo_akhir'],
                'jumlah_program_kerja' => ProgramKerja::query()
                    ->where('kelurahan_id', $kelurahan->id)
                    ->where('tahun', $tahun)
                    ->count(),
                'jumlah_inventaris' => InventarisBarang::query()
                    ->where('kelurahan_id', $kelurahan->id)
                    ->where('tahun', $tahun)
                    ->count(),
            ];
        });
    }
}
