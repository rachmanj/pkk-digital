<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveKelurahan;
use App\Http\Requests\StoreStrukturPengurusRequest;
use App\Http\Requests\UpdateStrukturPengurusRequest;
use App\Models\Pokja;
use App\Models\StrukturPengurus;
use App\Support\PkkPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StrukturController extends Controller
{
    use ResolvesActiveKelurahan;

    public function index(Request $request): View
    {
        $this->authorizeLihat();

        $kelurahan = $this->activeKelurahan();
        $unitFilter = $request->string('unit')->toString();

        $query = StrukturPengurus::query()->urut();
        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }
        if ($unitFilter !== '' && in_array($unitFilter, StrukturPengurus::unitNilai(), true)) {
            $query->unit($unitFilter);
        }

        $semua = $query->with('pokja')->get();

        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        $perUnit = $semua->groupBy('unit')->map(function (Collection $rows) use ($pokjaList) {
            $perJabatan = StrukturPengurus::kelompokkanPerJabatan($rows);

            return [
                'per_jabatan' => $perJabatan,
                'per_pokja' => StrukturPengurus::kelompokkanPerPokja(
                    $rows->where('unit', StrukturPengurus::UNIT_POKJA)
                ),
            ];
        });

        return view('struktur.index', [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'perUnit' => $perUnit,
            'filters' => [
                'unit' => $unitFilter,
            ],
            'canKelola' => auth()->user()?->can(PkkPermission::KELOLA_STRUKTUR) ?? false,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeKelola();

        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        return view('struktur.create', [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'unit' => $request->input('unit', StrukturPengurus::UNIT_TP_PKK),
        ]);
    }

    public function store(StoreStrukturPengurusRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('struktur.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $this->validasiKonteksUnit($validated, $kelurahan->id);

        StrukturPengurus::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'unit' => $validated['unit'],
            'pokja_id' => $validated['pokja_id'] ?? null,
            'rt' => $validated['rt'] ?? null,
            'jabatan' => $validated['jabatan'],
            'nama' => $validated['nama'],
            'urutan' => $validated['urutan'] ?? 0,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()->route('struktur.index', ['unit' => $validated['unit']])
            ->with('success', 'Pengurus berhasil ditambahkan.');
    }

    public function show(StrukturPengurus $struktur): View
    {
        $this->authorizeLihat();
        $this->authorizeKelurahanRecord($struktur);

        $struktur->load('pokja', 'kelurahan');

        return view('struktur.show', [
            'struktur' => $struktur,
            'canKelola' => auth()->user()?->can(PkkPermission::KELOLA_STRUKTUR) ?? false,
        ]);
    }

    public function edit(StrukturPengurus $struktur): View
    {
        $this->authorizeKelola();
        $this->authorizeKelurahanRecord($struktur);

        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        return view('struktur.edit', [
            'struktur' => $struktur,
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
        ]);
    }

    public function update(UpdateStrukturPengurusRequest $request, StrukturPengurus $struktur): RedirectResponse
    {
        $this->authorizeKelurahanRecord($struktur);

        $validated = $request->validated();
        $this->validasiKonteksUnit($validated, $struktur->kelurahan_id);

        $struktur->update([
            'unit' => $validated['unit'],
            'pokja_id' => $validated['pokja_id'] ?? null,
            'rt' => $validated['rt'] ?? null,
            'jabatan' => $validated['jabatan'],
            'nama' => $validated['nama'],
            'urutan' => $validated['urutan'] ?? 0,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()->route('struktur.show', $struktur)
            ->with('success', 'Data pengurus berhasil diperbarui.');
    }

    public function destroy(StrukturPengurus $struktur): RedirectResponse
    {
        $this->authorizeKelola();
        $this->authorizeKelurahanRecord($struktur);

        $unit = $struktur->unit;
        $struktur->delete();

        return redirect()->route('struktur.index', ['unit' => $unit])
            ->with('success', 'Pengurus berhasil dihapus.');
    }

    public function naik(StrukturPengurus $struktur): RedirectResponse
    {
        $this->authorizeKelola();
        $this->authorizeKelurahanRecord($struktur);
        $this->tukarUrutan($struktur, -1);

        return back()->with('success', 'Urutan diperbarui.');
    }

    public function turun(StrukturPengurus $struktur): RedirectResponse
    {
        $this->authorizeKelola();
        $this->authorizeKelurahanRecord($struktur);
        $this->tukarUrutan($struktur, 1);

        return back()->with('success', 'Urutan diperbarui.');
    }

    private function authorizeLihat(): void
    {
        $user = auth()->user();
        if ($user === null
            || (! $user->can(PkkPermission::LIHAT_STRUKTUR) && ! $user->can(PkkPermission::KELOLA_STRUKTUR))) {
            abort(403);
        }
    }

    private function authorizeKelola(): void
    {
        if (! auth()->user()?->can(PkkPermission::KELOLA_STRUKTUR)) {
            abort(403);
        }
    }

    private function authorizeKelurahanRecord(StrukturPengurus $struktur): void
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null || $struktur->kelurahan_id !== $kelurahan->id) {
            abort(404);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validasiKonteksUnit(array $validated, int $kelurahanId): void
    {
        $unit = $validated['unit'];
        if ($unit === StrukturPengurus::UNIT_POKJA) {
            $pokjaId = $validated['pokja_id'] ?? null;
            if ($pokjaId === null) {
                abort(422, 'Pokja wajib dipilih untuk unit pokja.');
            }
            $valid = Pokja::query()
                ->where('kelurahan_id', $kelurahanId)
                ->whereKey($pokjaId)
                ->exists();
            if (! $valid) {
                abort(422, 'Pokja tidak valid untuk kelurahan aktif.');
            }
        } else {
            $validated['pokja_id'] = null;
        }

        if (in_array($unit, [StrukturPengurus::UNIT_LBS, StrukturPengurus::UNIT_PHBS], true)) {
            if (empty($validated['rt'])) {
                abort(422, 'RT wajib diisi untuk unit LBS/PHBS.');
            }
        }
    }

    private function tukarUrutan(StrukturPengurus $struktur, int $arah): void
    {
        $scope = StrukturPengurus::query()
            ->where('kelurahan_id', $struktur->kelurahan_id)
            ->where('unit', $struktur->unit)
            ->when(
                $struktur->pokja_id,
                fn ($q) => $q->where('pokja_id', $struktur->pokja_id),
                fn ($q) => $q->whereNull('pokja_id')
            )
            ->when(
                $struktur->rt,
                fn ($q) => $q->where('rt', $struktur->rt),
                fn ($q) => $q->whereNull('rt')
            )
            ->where('jabatan', $struktur->jabatan)
            ->urut()
            ->get();

        $index = $scope->search(fn (StrukturPengurus $row) => $row->id === $struktur->id);
        if ($index === false) {
            return;
        }

        $targetIndex = $index + $arah;
        if ($targetIndex < 0 || $targetIndex >= $scope->count()) {
            return;
        }

        $lain = $scope[$targetIndex];
        $urutanSaatIni = $struktur->urutan;
        $struktur->update(['urutan' => $lain->urutan]);
        $lain->update(['urutan' => $urutanSaatIni]);
    }
}
