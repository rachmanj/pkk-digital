<?php

namespace App\Http\Controllers;

use App\Exports\AgendaSuratKeluarExport;
use App\Exports\AgendaSuratMasukExport;
use App\Exports\BukuKegiatanExport;
use App\Exports\BukuKunjunganExport;
use App\Exports\BukuTamuExport;
use App\Exports\DaftarAnggotaExport;
use App\Exports\DaftarAnggotaTpPkkExport;
use App\Exports\DaftarHadirExport;
use App\Exports\NotulenExport;
use App\Services\BukuCetakService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CetakController extends Controller
{
    /** @var array<string, class-string> */
    private const EXPORT_MAP = [
        'agenda_surat_masuk' => AgendaSuratMasukExport::class,
        'agenda_surat_keluar' => AgendaSuratKeluarExport::class,
        'daftar_hadir' => DaftarHadirExport::class,
        'buku_kegiatan' => BukuKegiatanExport::class,
        'notulen' => NotulenExport::class,
        'daftar_anggota' => DaftarAnggotaExport::class,
        'daftar_anggota_tp_pkk' => DaftarAnggotaTpPkkExport::class,
        'buku_tamu' => BukuTamuExport::class,
        'buku_kunjungan' => BukuKunjunganExport::class,
    ];

    public function __construct(private BukuCetakService $bukuCetakService) {}

    public function show(Request $request, string $buku): View
    {
        $dataset = $this->dataset($buku, $request);

        $view = $dataset['tipe'] === 'notulen'
            ? 'cetak.notulen'
            : 'cetak.tabel';

        return view($view, [
            'dataset' => $dataset,
            'mode' => 'screen',
        ]);
    }

    public function pdf(Request $request, string $buku): Response
    {
        $dataset = $this->dataset($buku, $request);

        $view = $dataset['tipe'] === 'notulen'
            ? 'cetak.notulen'
            : 'cetak.tabel';

        $html = view($view, [
            'dataset' => $dataset,
            'mode' => 'pdf',
        ])->render();

        $filename = $this->namaBerkas($buku, $dataset['tahun'], 'pdf');

        return Pdf::loadHTML($html)
            ->setPaper('folio', 'portrait')
            ->download($filename);
    }

    public function export(Request $request, string $buku): BinaryFileResponse
    {
        $dataset = $this->dataset($buku, $request);
        $exportClass = self::EXPORT_MAP[$buku] ?? null;
        if ($exportClass === null) {
            abort(404);
        }

        $filename = $this->namaBerkas($buku, $dataset['tahun'], 'xlsx');

        return Excel::download(new $exportClass($dataset), $filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataset(string $buku, Request $request): array
    {
        if (! BukuCetakService::kodeTerdaftar($buku)) {
            abort(404);
        }

        return $this->bukuCetakService->data($buku, $this->filterDariRequest($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function filterDariRequest(Request $request): array
    {
        return $request->only(['tahun', 'dari', 'sampai', 'pokja_id', 'kegiatan', 'buku']);
    }

    private function namaBerkas(string $buku, int|string $tahun, string $ext): string
    {
        return sprintf('%s-%s.%s', $buku, $tahun, $ext);
    }
}
