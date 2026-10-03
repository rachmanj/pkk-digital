<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesPokjaScope;
use App\Http\Controllers\Concerns\ResolvesActiveKelurahan;
use App\Http\Requests\StoreAgendaSuratRequest;
use App\Http\Requests\UpdateAgendaSuratRequest;
use App\Models\AgendaSurat;
use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgendaSuratController extends Controller
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
        $jenis = $request->string('jenis')->toString();
        if (! in_array($jenis, [AgendaSurat::JENIS_MASUK, AgendaSurat::JENIS_KELUAR], true)) {
            $jenis = AgendaSurat::JENIS_MASUK;
        }

        $buku = $request->string('buku')->toString();
        if ($buku === '') {
            $buku = $this->defaultBukuForKetuaPokja();
        }
        $pokjaIdFilter = null;
        if (str_starts_with($buku, 'pokja-')) {
            $pokjaIdFilter = (int) substr($buku, 6);
        }
        $this->authorizePokjaBukuFilter($pokjaIdFilter);

        $query = AgendaSurat::query()
            ->with(['disposisi.pokja', 'disposisi.user'])
            ->where('jenis', $jenis)
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
            $query->where(function ($q) use ($search, $jenis) {
                $q->where('no_surat', 'like', '%'.$search.'%')
                    ->orWhere('perihal', 'like', '%'.$search.'%');

                if ($jenis === AgendaSurat::JENIS_MASUK) {
                    $q->orWhere('dari', 'like', '%'.$search.'%');
                } else {
                    $q->orWhere('kepada', 'like', '%'.$search.'%');
                }
            });
        }

        $statusTindak = $request->string('status_tindak')->toString();
        if ($statusTindak !== '' && $jenis === AgendaSurat::JENIS_MASUK) {
            $surat = $query->get()->filter(function (AgendaSurat $item) use ($statusTindak) {
                return $item->statusTindakLanjut() === $statusTindak;
            });
            $page = max(1, (int) $request->input('page', 1));
            $perPage = 15;
            $items = $surat->slice(($page - 1) * $perPage, $perPage)->values();
            $agendaSurat = new LengthAwarePaginator(
                $items,
                $surat->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $agendaSurat = $query->paginate(15)->withQueryString();
        }

        return view('agenda-surat.index', [
            'agendaSurat' => $agendaSurat,
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'jenis' => $jenis,
            'filters' => [
                'tahun' => $tahun,
                'buku' => $buku !== '' ? $buku : 'kelurahan',
                'q' => $search,
                'status_tindak' => $statusTindak,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        $jenis = $request->string('jenis')->toString();
        if (! in_array($jenis, [AgendaSurat::JENIS_MASUK, AgendaSurat::JENIS_KELUAR], true)) {
            $jenis = AgendaSurat::JENIS_MASUK;
        }

        return view('agenda-surat.create', [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'jenis' => $jenis,
            'buku' => $request->input('buku', 'kelurahan'),
        ]);
    }

    public function store(StoreAgendaSuratRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('agenda-surat.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $pokjaId = $this->pokjaIdForKetuaPokjaWrite($pokjaId);
        $this->authorizePokjaRecord($pokjaId);
        $tahun = (int) date('Y', strtotime($validated['tanggal_surat']));

        $agendaSurat = DB::transaction(function () use ($validated, $kelurahan, $pokjaId, $tahun, $request) {
            $noUrut = AgendaSurat::nomorUrutBerikutnya(
                $kelurahan->id,
                $validated['jenis'],
                $pokjaId,
                $tahun
            );

            $surat = AgendaSurat::query()->create([
                'kelurahan_id' => $kelurahan->id,
                'jenis' => $validated['jenis'],
                'pokja_id' => $pokjaId,
                'no_urut_tahun' => $noUrut,
                'tahun' => $tahun,
                'tanggal_surat' => $validated['tanggal_surat'],
                'tanggal_terima' => $validated['tanggal_terima'] ?? null,
                'no_surat' => $validated['no_surat'],
                'dari' => $validated['dari'] ?? null,
                'kepada' => $validated['kepada'] ?? null,
                'perihal' => $validated['perihal'],
                'lampiran' => $validated['lampiran_keterangan'] ?? null,
                'tembusan' => $validated['tembusan'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $this->storeBerkas($request, $surat);

            return $surat;
        });

        return redirect()->route('agenda-surat.show', $agendaSurat)
            ->with('success', 'Agenda surat berhasil disimpan.');
    }

    public function show(AgendaSurat $agendaSurat): View
    {
        $this->authorizePokjaRecordRead($agendaSurat->pokja_id);

        $agendaSurat->load(['disposisi.pokja', 'disposisi.user', 'disposisi.olehUser', 'pokja', 'kelurahan']);
        $pokjaList = Pokja::query()
            ->where('kelurahan_id', $agendaSurat->kelurahan_id)
            ->orderBy('kode')
            ->get();
        $userList = User::query()->orderBy('name')->get();

        return view('agenda-surat.show', [
            'agendaSurat' => $agendaSurat,
            'pokjaList' => $pokjaList,
            'userList' => $userList,
        ]);
    }

    public function edit(AgendaSurat $agendaSurat): View
    {
        $this->authorizePokjaRecord($agendaSurat->pokja_id);

        $pokjaList = Pokja::query()
            ->where('kelurahan_id', $agendaSurat->kelurahan_id)
            ->orderBy('kode')
            ->get();

        return view('agenda-surat.edit', [
            'agendaSurat' => $agendaSurat,
            'pokjaList' => $pokjaList,
        ]);
    }

    public function update(UpdateAgendaSuratRequest $request, AgendaSurat $agendaSurat): RedirectResponse
    {
        $this->authorizePokjaRecord($agendaSurat->pokja_id);

        $validated = $request->validated();
        $pokjaId = isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null;
        $pokjaId = $this->pokjaIdForKetuaPokjaWrite($pokjaId);
        $this->authorizePokjaRecord($pokjaId);

        $agendaSurat->update([
            'jenis' => $validated['jenis'],
            'pokja_id' => $pokjaId,
            'tanggal_surat' => $validated['tanggal_surat'],
            'tanggal_terima' => $validated['tanggal_terima'] ?? null,
            'no_surat' => $validated['no_surat'],
            'dari' => $validated['dari'] ?? null,
            'kepada' => $validated['kepada'] ?? null,
            'perihal' => $validated['perihal'],
            'lampiran' => $validated['lampiran_keterangan'] ?? null,
            'tembusan' => $validated['tembusan'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        $this->storeBerkas($request, $agendaSurat);

        return redirect()->route('agenda-surat.show', $agendaSurat)
            ->with('success', 'Agenda surat berhasil diperbarui.');
    }

    public function destroy(AgendaSurat $agendaSurat): RedirectResponse
    {
        $this->authorizePokjaRecord($agendaSurat->pokja_id);

        if ($agendaSurat->file_path !== null) {
            Storage::disk('local')->delete($agendaSurat->file_path);
        }

        $agendaSurat->delete();

        return redirect()->route('agenda-surat.index')
            ->with('success', 'Agenda surat berhasil dihapus.');
    }

    public function berkas(AgendaSurat $agendaSurat): StreamedResponse
    {
        $this->authorizePokjaRecordRead($agendaSurat->pokja_id);

        if ($agendaSurat->file_path === null || ! Storage::disk('local')->exists($agendaSurat->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($agendaSurat->file_path);
    }

    private function storeBerkas(StoreAgendaSuratRequest|UpdateAgendaSuratRequest $request, AgendaSurat $agendaSurat): void
    {
        if (! $request->hasFile('lampiran')) {
            return;
        }

        $file = $request->file('lampiran');
        if ($file === null) {
            return;
        }

        if ($agendaSurat->file_path !== null) {
            Storage::disk('local')->delete($agendaSurat->file_path);
        }

        $path = $file->store('agenda-surat/'.$agendaSurat->id, 'local');
        $agendaSurat->update(['file_path' => $path]);
    }
}
