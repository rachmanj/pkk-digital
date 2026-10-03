<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesPokjaScope;
use App\Http\Requests\StoreKasSaldoAwalRequest;
use App\Http\Requests\StoreKasTransaksiRequest;
use App\Http\Requests\UpdateKasTransaksiRequest;
use App\Models\KasSaldoAwal;
use App\Models\KasTransaksi;
use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Services\KasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KasController extends Controller
{
    use HandlesPokjaScope;

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
        ]);
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

    private function activeKelurahan(): ?Kelurahan
    {
        return Kelurahan::query()->where('is_active', true)->first();
    }
}
