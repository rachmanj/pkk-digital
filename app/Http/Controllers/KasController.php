<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesPokjaScope;
use App\Http\Controllers\Concerns\ResolvesActiveKelurahan;
use App\Http\Requests\StoreKasSaldoAwalRequest;
use App\Http\Requests\StoreKasTransaksiRequest;
use App\Http\Requests\StoreKasTutupBukuRequest;
use App\Http\Requests\UpdateKasTransaksiRequest;
use App\Models\KasSaldoAwal;
use App\Models\KasTransaksi;
use App\Models\KasTutupBuku;
use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Services\KasService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class KasController extends Controller
{
    use HandlesPokjaScope;
    use ResolvesActiveKelurahan;

    public function __construct(
        private readonly KasService $kasService
    ) {}

    public function index(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        $tahun = (int) $request->input('tahun', now()->year);

        $buku = $request->string('buku')->toString();
        if ($buku === '') {
            $buku = $this->defaultBukuForKetuaPokja();
        }
        $pokjaIdFilter = $this->pokjaIdFromBukuParam($buku);
        $this->authorizePokjaBukuFilter($pokjaIdFilter);

        $query = KasTransaksi::query()
            ->tahun($tahun)
            ->orderBy('tanggal')
            ->orderBy('id');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($pokjaIdFilter) {
            $query->where('pokja_id', $pokjaIdFilter);
        } else {
            $query->whereNull('pokja_id');
        }

        $jenis = $request->string('jenis')->toString();
        if ($jenis !== '' && in_array($jenis, [KasTransaksi::JENIS_MASUK, KasTransaksi::JENIS_KELUAR], true)) {
            $query->where('jenis', $jenis);
        }

        $bulan = $request->input('bulan');
        if ($bulan !== null && $bulan !== '') {
            $query->whereMonth('tanggal', (int) $bulan);
        }

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('uraian', 'like', '%'.$search.'%')
                    ->orWhere('no_bukti', 'like', '%'.$search.'%');
            });
        }

        $transaksi = $query->paginate(20)->withQueryString();

        $ringkasan = $kelurahan
            ? $this->kasService->ringkasan($kelurahan->id, $pokjaIdFilter, $tahun)
            : null;

        $saldoTunai = $kelurahan
            ? $this->kasService->saldoAwal($kelurahan->id, $pokjaIdFilter, $tahun, KasTransaksi::POS_TUNAI)
            : 0;
        $saldoBank = $kelurahan
            ? $this->kasService->saldoAwal($kelurahan->id, $pokjaIdFilter, $tahun, KasTransaksi::POS_BANK)
            : 0;

        $tutupBukuRiwayat = ($kelurahan && $pokjaIdFilter === null)
            ? KasTutupBuku::query()
                ->where('kelurahan_id', $kelurahan->id)
                ->whereNull('pokja_id')
                ->tahun($tahun)
                ->orderByDesc('tanggal_tutup')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('kas.index', [
            'transaksi' => $transaksi,
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'ringkasan' => $ringkasan,
            'saldoAwalForm' => [
                'saldo_tunai' => $saldoTunai,
                'saldo_bank' => $saldoBank,
            ],
            'filters' => [
                'tahun' => $tahun,
                'buku' => $buku,
                'jenis' => $jenis,
                'bulan' => $bulan !== null && $bulan !== '' ? (int) $bulan : '',
                'q' => $search,
            ],
            'tutupBukuRiwayat' => $tutupBukuRiwayat,
        ]);
    }

    public function rekap(Request $request): View
    {
        $context = $this->rekapContext($request);

        return view('kas.rekap', $context);
    }

    public function rekapPdf(Request $request): Response
    {
        $context = $this->rekapContext($request);
        $html = view('kas.rekap-pdf', $context)->render();
        $filename = sprintf('rekap-kas-%s-%s.pdf', $context['filters']['buku'], $context['filters']['tahun']);

        return Pdf::loadHTML($html)->setPaper('folio', 'portrait')->download($filename);
    }

    public function storeTutupBuku(StoreKasTutupBukuRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('kas.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $pokjaId = $this->pokjaIdFromBukuParam($validated['buku']);
        $this->authorizePokjaRecord($pokjaId);

        $tahun = (int) $validated['tahun'];

        if ($pokjaId !== null) {
            return redirect()->route('kas.index', [
                'buku' => $validated['buku'],
                'tahun' => $tahun,
            ])->with('error', 'Tutup buku hanya berlaku untuk Buku Tabungan/Kas Umum tingkat kelurahan.');
        }

        $sisaBank = $this->kasService->saldoAkhir($kelurahan->id, $pokjaId, $tahun, KasTransaksi::POS_BANK);
        $sisaTunai = $this->kasService->saldoAkhir($kelurahan->id, $pokjaId, $tahun, KasTransaksi::POS_TUNAI);

        KasTutupBuku::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaId,
            'tahun' => $tahun,
            'tanggal_tutup' => $validated['tanggal_tutup'],
            'sisa_bank' => $sisaBank,
            'sisa_tunai' => $sisaTunai,
            'total' => $sisaBank + $sisaTunai,
            'catatan' => $validated['catatan'] ?? null,
            'nama_ketua' => $validated['nama_ketua'] ?? null,
            'nama_bendahara' => $validated['nama_bendahara'] ?? null,
            'ditutup_oleh' => $request->user()?->id,
        ]);

        return redirect()->route('kas.index', [
            'buku' => $validated['buku'],
            'tahun' => $tahun,
        ])->with('success', 'Buku kas berhasil ditutup.');
    }

    public function destroyTutupBuku(KasTutupBuku $kasTutupBuku): RedirectResponse
    {
        $this->authorizePokjaRecord($kasTutupBuku->pokja_id);

        $buku = $kasTutupBuku->pokja_id ? 'pokja-'.$kasTutupBuku->pokja_id : 'kelurahan';
        $tahun = $kasTutupBuku->tahun;

        if ($kasTutupBuku->pokja_id !== null) {
            return redirect()->route('kas.index', [
                'buku' => $buku,
                'tahun' => $tahun,
            ])->with('error', 'Tutup buku hanya berlaku untuk Buku Tabungan/Kas Umum tingkat kelurahan.');
        }

        $kasTutupBuku->delete();

        return redirect()->route('kas.index', [
            'buku' => $buku,
            'tahun' => $tahun,
        ])->with('success', 'Riwayat tutup buku dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rekapContext(Request $request): array
    {
        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        $tahun = (int) $request->input('tahun', now()->year);
        $buku = $request->string('buku')->toString();
        if ($buku === '') {
            $buku = $this->defaultBukuForKetuaPokja();
        }
        $pokjaIdFilter = $this->pokjaIdFromBukuParam($buku);
        $this->authorizePokjaBukuFilter($pokjaIdFilter);

        $rekap = $kelurahan
            ? $this->kasService->rekapBulanan($kelurahan->id, $pokjaIdFilter, $tahun)
            : [];

        $totalMasuk = array_sum(array_column($rekap, 'masuk'));
        $totalKeluar = array_sum(array_column($rekap, 'keluar'));
        $saldoAkhir = $rekap !== [] ? $rekap[array_key_last($rekap)]['saldo_akhir'] : 0.0;

        $judulBuku = $buku === 'kelurahan'
            ? 'Kelurahan'
            : 'Pokja '.($pokjaList->firstWhere('id', $pokjaIdFilter)?->kode ?? '');

        return [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'rekap' => $rekap,
            'total' => [
                'masuk' => $totalMasuk,
                'keluar' => $totalKeluar,
                'saldo_akhir' => $saldoAkhir,
            ],
            'judulBuku' => $judulBuku,
            'filters' => [
                'tahun' => $tahun,
                'buku' => $buku,
            ],
        ];
    }

    public function create(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        return view('kas.create', [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'buku' => $request->input('buku', 'kelurahan'),
        ]);
    }

    public function store(StoreKasTransaksiRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('kas.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $pokjaId = $this->pokjaIdForKetuaPokjaWrite($pokjaId);
        $this->authorizePokjaRecord($pokjaId);

        $transaksi = KasTransaksi::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaId,
            'jenis' => $validated['jenis'],
            'pos' => $validated['pos'],
            'tanggal' => $validated['tanggal'],
            'sumber_dana' => $validated['sumber_dana'] ?? null,
            'uraian' => $validated['uraian'],
            'no_bukti' => $validated['no_bukti'] ?? null,
            'jumlah' => $validated['jumlah'],
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('kas.show', $transaksi)
            ->with('success', 'Transaksi kas berhasil disimpan.');
    }

    public function show(KasTransaksi $kasTransaksi): View
    {
        $this->authorizePokjaRecordRead($kasTransaksi->pokja_id);

        $kasTransaksi->load(['pokja', 'kelurahan', 'pembuat']);

        return view('kas.show', [
            'transaksi' => $kasTransaksi,
        ]);
    }

    public function edit(KasTransaksi $kasTransaksi): View
    {
        $this->authorizePokjaRecord($kasTransaksi->pokja_id);

        $pokjaList = Pokja::query()
            ->where('kelurahan_id', $kasTransaksi->kelurahan_id)
            ->orderBy('kode')
            ->get();

        return view('kas.edit', [
            'transaksi' => $kasTransaksi,
            'pokjaList' => $pokjaList,
        ]);
    }

    public function update(UpdateKasTransaksiRequest $request, KasTransaksi $kasTransaksi): RedirectResponse
    {
        $this->authorizePokjaRecord($kasTransaksi->pokja_id);

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $pokjaId = $this->pokjaIdForKetuaPokjaWrite($pokjaId);
        $this->authorizePokjaRecord($pokjaId);

        $kasTransaksi->update([
            'pokja_id' => $pokjaId,
            'jenis' => $validated['jenis'],
            'pos' => $validated['pos'],
            'tanggal' => $validated['tanggal'],
            'sumber_dana' => $validated['sumber_dana'] ?? null,
            'uraian' => $validated['uraian'],
            'no_bukti' => $validated['no_bukti'] ?? null,
            'jumlah' => $validated['jumlah'],
        ]);

        return redirect()->route('kas.show', $kasTransaksi)
            ->with('success', 'Transaksi kas berhasil diperbarui.');
    }

    public function destroy(KasTransaksi $kasTransaksi): RedirectResponse
    {
        $this->authorizePokjaRecord($kasTransaksi->pokja_id);

        $kasTransaksi->delete();

        return redirect()->route('kas.index', [
            'buku' => $kasTransaksi->pokja_id ? 'pokja-'.$kasTransaksi->pokja_id : 'kelurahan',
            'tahun' => $kasTransaksi->tahun,
        ])->with('success', 'Transaksi kas berhasil dihapus.');
    }

    public function storeSaldoAwal(StoreKasSaldoAwalRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('kas.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $pokjaId = $this->pokjaIdFromBukuParam($validated['buku']);
        $this->authorizePokjaRecord($pokjaId);

        $tahun = (int) $validated['tahun'];

        $this->upsertSaldoAwal($kelurahan->id, $pokjaId, $tahun, KasSaldoAwal::POS_TUNAI, (float) $validated['saldo_tunai']);
        $this->upsertSaldoAwal($kelurahan->id, $pokjaId, $tahun, KasSaldoAwal::POS_BANK, (float) $validated['saldo_bank']);

        return redirect()->route('kas.index', [
            'buku' => $validated['buku'],
            'tahun' => $tahun,
        ])->with('success', 'Saldo awal berhasil disimpan.');
    }

    private function upsertSaldoAwal(int $kelurahanId, ?int $pokjaId, int $tahun, string $pos, float $jumlah): void
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

        $existing = $query->first();

        if ($existing !== null) {
            $existing->update(['jumlah' => $jumlah]);

            return;
        }

        KasSaldoAwal::query()->create([
            'kelurahan_id' => $kelurahanId,
            'pokja_id' => $pokjaId,
            'tahun' => $tahun,
            'pos' => $pos,
            'jumlah' => $jumlah,
        ]);
    }

}
