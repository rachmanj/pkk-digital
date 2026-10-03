<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesPokjaScope;
use App\Http\Controllers\Concerns\ResolvesActiveKelurahan;
use App\Http\Requests\StoreProgramKerjaRequest;
use App\Http\Requests\UpdateProgramKerjaRequest;
use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Models\ProgramKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramKerjaController extends Controller
{
    use HandlesPokjaScope;
    use ResolvesActiveKelurahan;

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

        $query = ProgramKerja::query()
            ->with('pokja')
            ->tahun($tahun)
            ->orderByRaw('CASE WHEN kode IS NULL OR kode = "" THEN 1 ELSE 0 END')
            ->orderBy('kode')
            ->orderBy('id');

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
                $q->where('kegiatan', 'like', '%'.$search.'%')
                    ->orWhere('program', 'like', '%'.$search.'%');
            });
        }

        $items = $query->paginate(20)->withQueryString();

        return view('program-kerja.index', [
            'items' => $items,
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

        return view('program-kerja.create', [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'buku' => $request->input('buku', 'kelurahan'),
            'tahun' => (int) $request->input('tahun', now()->year),
        ]);
    }

    public function store(StoreProgramKerjaRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('program-kerja.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $this->authorizePokjaRecord($pokjaId);
        $pokjaId = $this->pokjaIdForKetuaPokjaWrite($pokjaId);

        $item = ProgramKerja::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaId,
            'tahun' => (int) $validated['tahun'],
            'kode' => $validated['kode'] ?? null,
            'program' => $validated['program'] ?? null,
            'kegiatan' => $validated['kegiatan'],
            'tanggal_kegiatan' => $validated['tanggal_kegiatan'] ?? null,
            'tujuan' => $validated['tujuan'] ?? null,
            'sasaran' => $validated['sasaran'] ?? null,
            'tempat' => $validated['tempat'] ?? null,
            'sumber_dana' => $validated['sumber_dana'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'bulan_rencana' => ProgramKerja::normalisasiBulan($validated['bulan_rencana'] ?? null),
            'bulan_pelaksanaan' => ProgramKerja::normalisasiBulan($validated['bulan_pelaksanaan'] ?? null),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('program-kerja.show', $item)
            ->with('success', 'Program kerja berhasil disimpan.');
    }

    public function show(ProgramKerja $programKerja): View
    {
        $this->authorizePokjaRecordRead($programKerja->pokja_id);

        $programKerja->load([
            'kelurahan',
            'pokja',
            'pembuat',
            'kegiatanTertaut' => fn ($q) => $q->orderByDesc('tanggal')->orderByDesc('id'),
        ]);

        return view('program-kerja.show', [
            'programKerja' => $programKerja,
            'jumlahRealisasi' => $programKerja->kegiatanTertaut->count(),
        ]);
    }

    public function edit(ProgramKerja $programKerja): View
    {
        $this->authorizePokjaRecord($programKerja->pokja_id);

        $pokjaList = Pokja::query()
            ->where('kelurahan_id', $programKerja->kelurahan_id)
            ->orderBy('kode')
            ->get();

        return view('program-kerja.edit', [
            'programKerja' => $programKerja,
            'pokjaList' => $pokjaList,
        ]);
    }

    public function update(UpdateProgramKerjaRequest $request, ProgramKerja $programKerja): RedirectResponse
    {
        $this->authorizePokjaRecord($programKerja->pokja_id);

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $this->authorizePokjaRecord($pokjaId);
        $pokjaId = $this->pokjaIdForKetuaPokjaWrite($pokjaId);

        $programKerja->update([
            'pokja_id' => $pokjaId,
            'tahun' => (int) $validated['tahun'],
            'kode' => $validated['kode'] ?? null,
            'program' => $validated['program'] ?? null,
            'kegiatan' => $validated['kegiatan'],
            'tanggal_kegiatan' => $validated['tanggal_kegiatan'] ?? null,
            'tujuan' => $validated['tujuan'] ?? null,
            'sasaran' => $validated['sasaran'] ?? null,
            'tempat' => $validated['tempat'] ?? null,
            'sumber_dana' => $validated['sumber_dana'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'bulan_rencana' => ProgramKerja::normalisasiBulan($validated['bulan_rencana'] ?? null),
            'bulan_pelaksanaan' => ProgramKerja::normalisasiBulan($validated['bulan_pelaksanaan'] ?? null),
        ]);

        return redirect()->route('program-kerja.show', $programKerja)
            ->with('success', 'Program kerja berhasil diperbarui.');
    }

    public function destroy(ProgramKerja $programKerja): RedirectResponse
    {
        $this->authorizePokjaRecord($programKerja->pokja_id);

        $programKerja->delete();

        return redirect()->route('program-kerja.index')
            ->with('success', 'Program kerja berhasil dihapus.');
    }

}
