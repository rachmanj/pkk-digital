<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBukuKunjunganRequest;
use App\Http\Requests\UpdateBukuKunjunganRequest;
use App\Models\BukuKunjungan;
use App\Models\Kelurahan;
use App\Models\Orang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BukuKunjunganController extends Controller
{
    public function index(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();

        $tahun = (int) $request->input('tahun', now()->year);

        $query = BukuKunjungan::query()
            ->with('orang')
            ->tahun($tahun)
            ->orderBy('no_urut_tahun');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('lokasi_kunjungan', 'like', '%'.$search.'%')
                    ->orWhere('jenis_kegiatan', 'like', '%'.$search.'%');
            });
        }

        $bukuKunjungan = $query->paginate(15)->withQueryString();

        return view('buku-kunjungan.index', [
            'bukuKunjungan' => $bukuKunjungan,
            'kelurahan' => $kelurahan,
            'filters' => [
                'tahun' => $tahun,
                'q' => $search,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();
        $orangList = $kelurahan
            ? Orang::query()->where('kelurahan_id', $kelurahan->id)->orderBy('nama')->limit(500)->get()
            : collect();

        return view('buku-kunjungan.create', [
            'kelurahan' => $kelurahan,
            'orangList' => $orangList,
        ]);
    }

    public function store(StoreBukuKunjunganRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('buku-kunjungan.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $orangId = isset($validated['orang_id']) ? (int) $validated['orang_id'] : null;
        $tahun = (int) date('Y', strtotime($validated['tanggal']));

        $bukuKunjungan = DB::transaction(function () use ($validated, $kelurahan, $orangId, $tahun) {
            $noUrut = BukuKunjungan::nomorUrutBerikutnya($kelurahan->id, $tahun);

            return BukuKunjungan::query()->create([
                'kelurahan_id' => $kelurahan->id,
                'no_urut_tahun' => $noUrut,
                'tahun' => $tahun,
                'tanggal' => $validated['tanggal'],
                'orang_id' => $orangId,
                'nama' => $validated['nama'],
                'jabatan' => $validated['jabatan'] ?? null,
                'lokasi_kunjungan' => $validated['lokasi_kunjungan'] ?? null,
                'jenis_kegiatan' => $validated['jenis_kegiatan'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        });

        return redirect()->route('buku-kunjungan.show', $bukuKunjungan)
            ->with('success', 'Buku kunjungan berhasil disimpan.');
    }

    public function show(BukuKunjungan $bukuKunjungan): View
    {
        $bukuKunjungan->load(['orang', 'kelurahan']);

        return view('buku-kunjungan.show', [
            'bukuKunjungan' => $bukuKunjungan,
        ]);
    }

    public function edit(BukuKunjungan $bukuKunjungan): View
    {
        $orangList = Orang::query()
            ->where('kelurahan_id', $bukuKunjungan->kelurahan_id)
            ->orderBy('nama')
            ->limit(500)
            ->get();

        return view('buku-kunjungan.edit', [
            'bukuKunjungan' => $bukuKunjungan,
            'orangList' => $orangList,
        ]);
    }

    public function update(UpdateBukuKunjunganRequest $request, BukuKunjungan $bukuKunjungan): RedirectResponse
    {
        $validated = $request->validated();
        $orangId = isset($validated['orang_id']) ? (int) $validated['orang_id'] : null;

        $bukuKunjungan->update([
            'tanggal' => $validated['tanggal'],
            'orang_id' => $orangId,
            'nama' => $validated['nama'],
            'jabatan' => $validated['jabatan'] ?? null,
            'lokasi_kunjungan' => $validated['lokasi_kunjungan'] ?? null,
            'jenis_kegiatan' => $validated['jenis_kegiatan'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()->route('buku-kunjungan.show', $bukuKunjungan)
            ->with('success', 'Buku kunjungan berhasil diperbarui.');
    }

    public function destroy(BukuKunjungan $bukuKunjungan): RedirectResponse
    {
        $bukuKunjungan->delete();

        return redirect()->route('buku-kunjungan.index')
            ->with('success', 'Buku kunjungan berhasil dihapus.');
    }

    private function activeKelurahan(): ?Kelurahan
    {
        return Kelurahan::query()->where('is_active', true)->first();
    }
}
