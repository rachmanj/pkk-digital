<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesPokjaScope;
use App\Http\Requests\StoreInventarisBarangRequest;
use App\Http\Requests\UpdateInventarisBarangRequest;
use App\Models\InventarisBarang;
use App\Models\Kelurahan;
use App\Models\Pokja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventarisController extends Controller
{
    use HandlesPokjaScope;

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

        $query = InventarisBarang::query()
            ->tahun($tahun)
            ->orderBy('nama_barang')
            ->orderBy('id');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($pokjaIdFilter) {
            $query->where('pokja_id', $pokjaIdFilter);
        } else {
            $query->whereNull('pokja_id');
        }

        $kondisi = $request->string('kondisi')->toString();
        if ($kondisi !== '' && in_array($kondisi, InventarisBarang::kondisiNilai(), true)) {
            $query->where('kondisi', $kondisi);
        }

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', '%'.$search.'%')
                    ->orWhere('asal_barang', 'like', '%'.$search.'%')
                    ->orWhere('tempat_penyimpanan', 'like', '%'.$search.'%');
            });
        }

        $ringkasanQuery = clone $query;
        $ringkasan = [
            'jenis_barang' => (clone $ringkasanQuery)->count(),
            'total_unit' => (int) (clone $ringkasanQuery)->sum('jumlah'),
            'baik' => (clone $ringkasanQuery)->where('kondisi', InventarisBarang::KONDISI_BAIK)->count(),
            'rusak_ringan' => (clone $ringkasanQuery)->where('kondisi', InventarisBarang::KONDISI_RUSAK_RINGAN)->count(),
            'rusak_berat' => (clone $ringkasanQuery)->where('kondisi', InventarisBarang::KONDISI_RUSAK_BERAT)->count(),
        ];

        $barang = $query->paginate(20)->withQueryString();

        return view('inventaris.index', [
            'barang' => $barang,
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'ringkasan' => $ringkasan,
            'filters' => [
                'tahun' => $tahun,
                'buku' => $buku,
                'kondisi' => $kondisi,
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

        return view('inventaris.create', [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'buku' => $request->input('buku', 'kelurahan'),
        ]);
    }

    public function store(StoreInventarisBarangRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('inventaris.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $pokjaId = $this->pokjaIdForKetuaPokjaWrite($pokjaId);
        $this->authorizePokjaRecord($pokjaId);

        $barang = InventarisBarang::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaId,
            'tahun' => (int) date('Y', strtotime($validated['tanggal_terima'])),
            'nama_barang' => $validated['nama_barang'],
            'asal_barang' => $validated['asal_barang'] ?? null,
            'tanggal_terima' => $validated['tanggal_terima'],
            'jumlah' => (int) $validated['jumlah'],
            'tempat_penyimpanan' => $validated['tempat_penyimpanan'] ?? null,
            'kondisi' => $validated['kondisi'],
            'keterangan' => $validated['keterangan'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('inventaris.show', $barang)
            ->with('success', 'Inventaris barang berhasil disimpan.');
    }

    public function show(InventarisBarang $inventarisBarang): View
    {
        $this->authorizePokjaRecordRead($inventarisBarang->pokja_id);

        $inventarisBarang->load(['pokja', 'kelurahan', 'pencatat']);

        return view('inventaris.show', [
            'inventarisBarang' => $inventarisBarang,
        ]);
    }

    public function edit(InventarisBarang $inventarisBarang): View
    {
        $this->authorizePokjaRecord($inventarisBarang->pokja_id);

        $pokjaList = Pokja::query()
            ->where('kelurahan_id', $inventarisBarang->kelurahan_id)
            ->orderBy('kode')
            ->get();

        return view('inventaris.edit', [
            'inventarisBarang' => $inventarisBarang,
            'pokjaList' => $pokjaList,
        ]);
    }

    public function update(UpdateInventarisBarangRequest $request, InventarisBarang $inventarisBarang): RedirectResponse
    {
        $this->authorizePokjaRecord($inventarisBarang->pokja_id);

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $pokjaId = $this->pokjaIdForKetuaPokjaWrite($pokjaId);
        $this->authorizePokjaRecord($pokjaId);

        $inventarisBarang->update([
            'pokja_id' => $pokjaId,
            'nama_barang' => $validated['nama_barang'],
            'asal_barang' => $validated['asal_barang'] ?? null,
            'tanggal_terima' => $validated['tanggal_terima'],
            'jumlah' => (int) $validated['jumlah'],
            'tempat_penyimpanan' => $validated['tempat_penyimpanan'] ?? null,
            'kondisi' => $validated['kondisi'],
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()->route('inventaris.show', $inventarisBarang)
            ->with('success', 'Inventaris barang berhasil diperbarui.');
    }

    public function destroy(InventarisBarang $inventarisBarang): RedirectResponse
    {
        $this->authorizePokjaRecord($inventarisBarang->pokja_id);

        $inventarisBarang->delete();

        return redirect()->route('inventaris.index')
            ->with('success', 'Inventaris barang berhasil dihapus.');
    }

    private function activeKelurahan(): ?Kelurahan
    {
        return Kelurahan::query()->where('is_active', true)->first();
    }
}
