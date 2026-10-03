<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrangRequest;
use App\Http\Requests\UpdateOrangRequest;
use App\Models\Keanggotaan;
use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\Rt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrangController extends Controller
{
    public function index(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        $query = Orang::query()
            ->with([
                'keanggotaan' => fn ($q) => $q->with('pokja')->orderByDesc('is_aktif'),
            ])
            ->orderBy('nama');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('alamat', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('pokja_id')) {
            $pokjaId = (int) $request->input('pokja_id');
            $query->whereHas('keanggotaan', fn ($q) => $q->where('pokja_id', $pokjaId));
        }

        if ($request->filled('jenis')) {
            $jenis = $request->string('jenis')->toString();
            $query->whereHas('keanggotaan', fn ($q) => $q->where('jenis', $jenis));
        }

        if ($request->filled('aktif')) {
            $aktif = $request->string('aktif')->toString() === '1';
            $query->whereHas('keanggotaan', fn ($q) => $q->where('is_aktif', $aktif));
        }

        $orang = $query->paginate(15)->withQueryString();

        return view('orang.index', [
            'orang' => $orang,
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'filters' => [
                'q' => $search,
                'pokja_id' => $request->input('pokja_id'),
                'jenis' => $request->input('jenis'),
                'aktif' => $request->input('aktif'),
            ],
        ]);
    }

    public function daftarAnggota(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();

        $query = Orang::query()
            ->with(['keanggotaan' => fn ($q) => $q->orderByDesc('is_aktif')])
            ->orderBy('nama');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('alamat', 'like', '%'.$search.'%');
            });
        }

        $orang = $query->paginate(25)->withQueryString();

        return view('orang.daftar-anggota', [
            'orang' => $orang,
            'kelurahan' => $kelurahan,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(): View|RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('orang.index')
                ->with('error', 'Belum ada kelurahan aktif. Jalankan seeder master terlebih dahulu.');
        }

        return view('orang.create', [
            'kelurahan' => $kelurahan,
            'pokjaList' => Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get(),
            'rtList' => Rt::query()->where('kelurahan_id', $kelurahan->id)->orderBy('nomor')->get(),
        ]);
    }

    public function store(StoreOrangRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('orang.index')
                ->with('error', 'Belum ada kelurahan aktif.');
        }

        $orang = DB::transaction(function () use ($request, $kelurahan) {
            $orang = Orang::query()->create([
                'kelurahan_id' => $kelurahan->id,
                'nama' => $request->string('nama')->toString(),
                'jenis_kelamin' => $request->string('jenis_kelamin')->toString(),
                'tempat_lahir' => $request->input('tempat_lahir'),
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'status_perkawinan' => $request->input('status_perkawinan'),
                'alamat' => $request->input('alamat'),
                'rt_id' => $request->input('rt_id'),
                'pendidikan' => $request->input('pendidikan'),
                'pekerjaan' => $request->input('pekerjaan'),
                'no_hp' => $request->input('no_hp'),
                'catatan' => $request->input('catatan'),
            ]);

            $this->syncKeanggotaan($orang, $kelurahan, $request->input('keanggotaan', []));
            $this->storeFoto($request, $orang);

            return $orang;
        });

        return redirect()->route('orang.show', $orang)
            ->with('success', 'Data anggota berhasil disimpan.');
    }

    public function show(Orang $orang): View
    {
        $orang->load([
            'kelurahan',
            'rt',
            'keanggotaan' => fn ($q) => $q->with('pokja')->orderByDesc('is_aktif'),
        ]);

        return view('orang.show', ['orang' => $orang]);
    }

    public function edit(Orang $orang): View
    {
        $kelurahan = $orang->kelurahan;
        $orang->load(['keanggotaan' => fn ($q) => $q->with('pokja')]);

        return view('orang.edit', [
            'orang' => $orang,
            'kelurahan' => $kelurahan,
            'pokjaList' => Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get(),
            'rtList' => Rt::query()->where('kelurahan_id', $kelurahan->id)->orderBy('nomor')->get(),
        ]);
    }

    public function update(UpdateOrangRequest $request, Orang $orang): RedirectResponse
    {
        DB::transaction(function () use ($request, $orang) {
            $orang->update([
                'nama' => $request->string('nama')->toString(),
                'jenis_kelamin' => $request->string('jenis_kelamin')->toString(),
                'tempat_lahir' => $request->input('tempat_lahir'),
                'tanggal_lahir' => $request->input('tanggal_lahir'),
                'status_perkawinan' => $request->input('status_perkawinan'),
                'alamat' => $request->input('alamat'),
                'rt_id' => $request->input('rt_id'),
                'pendidikan' => $request->input('pendidikan'),
                'pekerjaan' => $request->input('pekerjaan'),
                'no_hp' => $request->input('no_hp'),
                'catatan' => $request->input('catatan'),
            ]);

            $this->syncKeanggotaan($orang, $orang->kelurahan, $request->input('keanggotaan', []));
            $this->storeFoto($request, $orang);
        });

        return redirect()->route('orang.show', $orang)
            ->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Orang $orang): RedirectResponse
    {
        if ($orang->isDipakaiSebagaiPimpinanKegiatan()) {
            return redirect()->route('orang.show', $orang)
                ->with('error', 'Anggota tidak dapat dihapus karena masih tercatat sebagai pimpinan kegiatan.');
        }

        DB::transaction(function () use ($orang) {
            $orang->keanggotaan()->delete();

            if ($orang->foto_path !== null) {
                Storage::disk('local')->delete($orang->foto_path);
            }

            $orang->delete();
        });

        return redirect()->route('orang.index')
            ->with('success', 'Data anggota berhasil dihapus.');
    }

    public function foto(Orang $orang): StreamedResponse
    {
        if ($orang->foto_path === null || ! Storage::disk('local')->exists($orang->foto_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($orang->foto_path);
    }

    private function activeKelurahan(): ?Kelurahan
    {
        return Kelurahan::query()->where('is_active', true)->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncKeanggotaan(Orang $orang, Kelurahan $kelurahan, array $items): void
    {
        $keptIds = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $payload = [
                'kelurahan_id' => $kelurahan->id,
                'jenis' => $item['jenis'],
                'pokja_id' => $item['pokja_id'] ?? null,
                'jabatan' => $item['jabatan'] ?? null,
                'no_registrasi' => $item['no_registrasi'] ?? null,
                'sk_nomor' => $item['sk_nomor'] ?? null,
                'mulai' => $item['mulai'] ?? null,
                'selesai' => $item['selesai'] ?? null,
                'is_aktif' => filter_var($item['is_aktif'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];

            if (! empty($item['id'])) {
                $keanggotaan = Keanggotaan::query()
                    ->where('orang_id', $orang->id)
                    ->whereKey($item['id'])
                    ->first();

                if ($keanggotaan !== null) {
                    $keanggotaan->update($payload);
                    $keptIds[] = $keanggotaan->id;

                    continue;
                }
            }

            $created = $orang->keanggotaan()->create($payload);
            $keptIds[] = $created->id;
        }

        $orang->keanggotaan()->whereNotIn('id', $keptIds)->delete();
    }

    private function storeFoto(StoreOrangRequest|UpdateOrangRequest $request, Orang $orang): void
    {
        if (! $request->hasFile('foto')) {
            return;
        }

        $file = $request->file('foto');
        if ($file === null) {
            return;
        }

        if ($orang->foto_path !== null) {
            Storage::disk('local')->delete($orang->foto_path);
        }

        $path = $file->store('orang-foto/'.$orang->id, 'local');
        $orang->update(['foto_path' => $path]);
    }
}
