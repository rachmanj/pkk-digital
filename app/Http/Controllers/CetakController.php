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
use App\Exports\InventarisExport;
use App\Exports\KasPokjaExport;
use App\Exports\KasTabunganExport;
use App\Exports\LaporanKotaExport;
use App\Exports\NotulenExport;
use App\Exports\ProgramKerjaExport;
use App\Exports\ProgramKerjaMatriksExport;
use App\Exports\StrukturLbsExport;
use App\Exports\StrukturPkkExport;
use App\Http\Controllers\Concerns\HandlesPokjaScope;
use App\Models\KasTutupBuku;
use App\Services\BukuCetakService;
use App\Services\BukuCetakTampilan;
use App\Support\PkkPermission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CetakController extends Controller
{
    use HandlesPokjaScope;

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
        'buku_inventaris' => InventarisExport::class,
        'kas_pokja' => KasPokjaExport::class,
        'kas_tabungan' => KasTabunganExport::class,
        'program_kerja' => ProgramKerjaExport::class,
        'program_kerja_matriks' => ProgramKerjaMatriksExport::class,
        'struktur_pkk' => StrukturPkkExport::class,
        'struktur_lbs' => StrukturLbsExport::class,
        'laporan_kota' => LaporanKotaExport::class,
    ];

    /** @var list<string> */
    private const LAPORAN_KOTA_BUKU = ['laporan_kota'];

    /** @var list<string> */
    private const STRUKTUR_BUKU = ['struktur_pkk', 'struktur_lbs'];

    /** @var list<string> */
    private const KAS_BUKU = ['kas_pokja', 'kas_tabungan'];

    /** @var list<string> */
    private const INVENTARIS_BUKU = ['buku_inventaris'];

    /** @var list<string> */
    private const PROGRAM_KERJA_BUKU = ['program_kerja', 'program_kerja_matriks'];

    public function __construct(
        private BukuCetakService $bukuCetakService,
        private BukuCetakTampilan $bukuCetakTampilan,
    ) {}

    public function show(Request $request, string $buku): View
    {
        $dataset = $this->dataset($buku, $request);

        $view = $this->viewUntukDataset($dataset);

        return view($view, [
            'dataset' => $dataset,
            'mode' => 'screen',
            'tampilan' => $this->bukuCetakTampilan->untukKode($buku),
        ]);
    }

    public function pdf(Request $request, string $buku): Response
    {
        $dataset = $this->dataset($buku, $request);

        $view = $this->viewUntukDataset($dataset);

        $tampilan = $this->bukuCetakTampilan->untukKode($buku);

        $html = view($view, [
            'dataset' => $dataset,
            'mode' => 'pdf',
            'tampilan' => $tampilan,
        ])->render();

        $filename = $this->namaBerkas($buku, $dataset['tahun'], 'pdf');

        return Pdf::loadHTML($html)
            ->setPaper($tampilan['dompdf_kertas'], $tampilan['orientasi'])
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

        $this->authorizeAksesCetak($buku);

        $filter = $this->filterDariRequest($request);
        $this->authorizeCetakPokja($buku, $filter);

        $dataset = $this->bukuCetakService->data($buku, $filter);

        $tutupBukuId = isset($filter['tutup_buku']) && $filter['tutup_buku'] !== ''
            ? (int) $filter['tutup_buku']
            : null;

        if ($buku === 'kas_tabungan' && $tutupBukuId !== null) {
            $dataset['tutup_buku'] = $this->muatTutupBukuUntukCetak($tutupBukuId);
        }

        return $dataset;
    }

    private function authorizeAksesCetak(string $buku): void
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        if (in_array($buku, self::KAS_BUKU, true)) {
            if (! $user->can(PkkPermission::LIHAT_KAS) && ! $user->can(PkkPermission::KELOLA_KAS)) {
                abort(403);
            }

            return;
        }

        if (in_array($buku, self::INVENTARIS_BUKU, true)) {
            if (! $user->can(PkkPermission::LIHAT_INVENTARIS) && ! $user->can(PkkPermission::KELOLA_INVENTARIS)) {
                abort(403);
            }

            return;
        }

        if (in_array($buku, self::PROGRAM_KERJA_BUKU, true)) {
            if (! $user->can(PkkPermission::LIHAT_PROGRAM_KERJA) && ! $user->can(PkkPermission::KELOLA_PROGRAM_KERJA)) {
                abort(403);
            }

            return;
        }

        if (in_array($buku, self::STRUKTUR_BUKU, true)) {
            if (! $user->can(PkkPermission::LIHAT_STRUKTUR) && ! $user->can(PkkPermission::KELOLA_STRUKTUR)) {
                abort(403);
            }

            return;
        }

        if (in_array($buku, self::LAPORAN_KOTA_BUKU, true)) {
            if ($user->can(PkkPermission::LIHAT_BUKU) || $user->can(PkkPermission::VERIFIKASI_BUKU)) {
                return;
            }
            if ($user->can(PkkPermission::LIHAT_KAS)) {
                return;
            }

            abort(403);
        }

        if (! $user->can(PkkPermission::LIHAT_BUKU) && ! $user->can(PkkPermission::VERIFIKASI_BUKU)) {
            abort(403);
        }
    }

    /**
     * @param  array<string, mixed>  $dataset
     */
    private function viewUntukDataset(array $dataset): string
    {
        if (isset($dataset['tutup_buku']) && $dataset['tutup_buku'] !== null) {
            return 'cetak.kas_tutup_bukti';
        }

        if (($dataset['tipe'] ?? '') === 'notulen') {
            return 'cetak.notulen';
        }

        if (($dataset['tipe'] ?? '') === 'kas_tabungan') {
            return 'cetak.kas_tabungan';
        }

        if (($dataset['tipe'] ?? '') === 'program_kerja_matriks') {
            return 'cetak.program_kerja_matriks';
        }

        if (($dataset['tipe'] ?? '') === 'struktur_pkk') {
            return 'cetak.struktur_pkk';
        }

        if (($dataset['tipe'] ?? '') === 'struktur_lbs') {
            return 'cetak.struktur_lbs';
        }

        if (($dataset['tipe'] ?? '') === 'laporan_kota') {
            return 'cetak.laporan_kota';
        }

        return 'cetak.tabel';
    }

    /**
     * @return array<string, mixed>
     */
    private function muatTutupBukuUntukCetak(int $id): array
    {
        $record = KasTutupBuku::query()->findOrFail($id);
        $this->authorizePokjaBukuFilter($record->pokja_id);

        return [
            'record' => $record,
        ];
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    private function authorizeCetakPokja(string $buku, array $filter): void
    {
        if (in_array($buku, self::KAS_BUKU, true)
            || in_array($buku, self::INVENTARIS_BUKU, true)
            || in_array($buku, self::PROGRAM_KERJA_BUKU, true)) {
            $pokjaId = $filter['pokja_id'] ?? null;
            if ($pokjaId === null && isset($filter['buku']) && is_string($filter['buku']) && str_starts_with($filter['buku'], 'pokja-')) {
                $pokjaId = (int) substr($filter['buku'], 6);
            }
            $this->authorizePokjaBukuFilter($pokjaId);

            return;
        }

        if ($this->ketuaPokjaPokjaId() === null) {
            return;
        }

        $pokjaId = null;
        if (str_starts_with($buku, 'pokja-')) {
            $pokjaId = (int) substr($buku, 6);
        } elseif (isset($filter['pokja_id']) && $filter['pokja_id'] !== '') {
            $pokjaId = (int) $filter['pokja_id'];
        } elseif (isset($filter['buku']) && is_string($filter['buku']) && str_starts_with($filter['buku'], 'pokja-')) {
            $pokjaId = (int) substr($filter['buku'], 6);
        }

        $this->authorizePokjaBukuFilter($pokjaId);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterDariRequest(Request $request): array
    {
        return $request->only(['tahun', 'bulan', 'dari', 'sampai', 'pokja_id', 'unit', 'kegiatan', 'buku', 'tutup_buku', 'rt']);
    }

    private function namaBerkas(string $buku, int|string $tahun, string $ext): string
    {
        return sprintf('%s-%s.%s', $buku, $tahun, $ext);
    }
}
