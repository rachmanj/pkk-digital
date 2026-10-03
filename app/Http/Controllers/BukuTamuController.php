<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBukuTamuRequest;
use App\Http\Requests\UpdateBukuTamuRequest;
use App\Models\BukuTamu;
use App\Models\Kelurahan;
use App\Models\Pokja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BukuTamuController extends Controller
{
    public function index(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        $tahun = (int) $request->input('tahun', now()->year);

        $buku = $request->string('buku')->toString();
        if ($buku === '') {
            $buku = 'kelurahan';
        }
        $pokjaIdFilter = null;
        if (str_starts_with($buku, 'pokja-')) {
            $pokjaIdFilter = (int) substr($buku, 6);
        }

        $query = BukuTamu::query()
            ->tahun($tahun)
            ->orderBy('no_urut_tahun');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($pokjaIdFilter) {
            $query->where('pokja_id', $pokjaIdFilter);
        } else {
            $query->whereNull('pokja_id');
        }

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_tamu', 'like', '%'.$search.'%')
                    ->orWhere('keperluan', 'like', '%'.$search.'%')
                    ->orWhere('tujuan', 'like', '%'.$search.'%');
            });
        }

        $bukuTamu = $query->paginate(15)->withQueryString();

        return view('buku-tamu.index', [
            'bukuTamu' => $bukuTamu,
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'filters' => [
                'tahun' => $tahun,
                'buku' => $buku,
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

        return view('buku-tamu.create', [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'buku' => $request->input('buku', 'kelurahan'),
        ]);
    }

    public function store(StoreBukuTamuRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('buku-tamu.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $tahun = (int) date('Y', strtotime($validated['tanggal']));

        $bukuTamu = DB::transaction(function () use ($validated, $kelurahan, $pokjaId, $tahun) {
            $noUrut = BukuTamu::nomorUrutBerikutnya($kelurahan->id, $pokjaId, $tahun);

            return BukuTamu::query()->create([
                'kelurahan_id' => $kelurahan->id,
                'pokja_id' => $pokjaId,
                'no_urut_tahun' => $noUrut,
                'tahun' => $tahun,
                'tanggal' => $validated['tanggal'],
                'nama_tamu' => $validated['nama_tamu'],
                'alamat' => $validated['alamat'] ?? null,
                'keperluan' => $validated['keperluan'] ?? null,
                'tujuan' => $validated['tujuan'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        });

        return redirect()->route('buku-tamu.show', $bukuTamu)
            ->with('success', 'Buku tamu berhasil disimpan.');
    }

    public function show(BukuTamu $bukuTamu): View
    {
        $bukuTamu->load(['pokja', 'kelurahan']);

        return view('buku-tamu.show', [
            'bukuTamu' => $bukuTamu,
        ]);
    }

    public function edit(BukuTamu $bukuTamu): View
    {
        $pokjaList = Pokja::query()
            ->where('kelurahan_id', $bukuTamu->kelurahan_id)
            ->orderBy('kode')
            ->get();

        return view('buku-tamu.edit', [
            'bukuTamu' => $bukuTamu,
            'pokjaList' => $pokjaList,
        ]);
    }

    public function update(UpdateBukuTamuRequest $request, BukuTamu $bukuTamu): RedirectResponse
    {
        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;

        $bukuTamu->update([
            'pokja_id' => $pokjaId,
            'tanggal' => $validated['tanggal'],
            'nama_tamu' => $validated['nama_tamu'],
            'alamat' => $validated['alamat'] ?? null,
            'keperluan' => $validated['keperluan'] ?? null,
            'tujuan' => $validated['tujuan'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()->route('buku-tamu.show', $bukuTamu)
            ->with('success', 'Buku tamu berhasil diperbarui.');
    }

    public function destroy(BukuTamu $bukuTamu): RedirectResponse
    {
        $bukuTamu->delete();

        return redirect()->route('buku-tamu.index')
            ->with('success', 'Buku tamu berhasil dihapus.');
    }

    private function activeKelurahan(): ?Kelurahan
    {
        return Kelurahan::query()->where('is_active', true)->first();
    }
}
