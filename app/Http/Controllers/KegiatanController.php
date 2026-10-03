<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKegiatanRequest;
use App\Http\Requests\UpdateKegiatanRequest;
use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Notulen;
use App\Models\Orang;
use App\Models\Pokja;
use App\Models\Presensi;
use App\Models\Rt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KegiatanController extends Controller
{
    public function index(Request $request): View
    {
        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = $request->input('bulan');
        $bulanInt = ($bulan !== null && $bulan !== '') ? (int) $bulan : null;

        $jenis = $request->string('jenis')->toString();
        if ($jenis !== '' && ! in_array($jenis, Kegiatan::daftarJenis(), true)) {
            $jenis = '';
        }

        $pokjaId = $request->input('pokja_id');
        $pokjaIdFilter = ($pokjaId !== null && $pokjaId !== '') ? (int) $pokjaId : null;

        $query = Kegiatan::query()
            ->with('pokja')
            ->withCount('presensi')
            ->tahun($tahun)
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }

        if ($bulanInt !== null && $bulanInt >= 1 && $bulanInt <= 12) {
            $query->bulan($bulanInt);
        }

        if ($jenis !== '') {
            $query->where('jenis', $jenis);
        }

        if ($pokjaIdFilter !== null) {
            $query->where('pokja_id', $pokjaIdFilter);
        }

        $search = $request->string('q')->trim()->toString();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhere('tempat', 'like', '%'.$search.'%');
            });
        }

        $kegiatan = $query->paginate(15)->withQueryString();

        return view('kegiatan.index', [
            'kegiatan' => $kegiatan,
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'jenisList' => Kegiatan::daftarJenis(),
            'filters' => [
                'tahun' => $tahun,
                'bulan' => $bulanInt,
                'jenis' => $jenis,
                'pokja_id' => $pokjaIdFilter,
                'q' => $search,
            ],
        ]);
    }

    public function create(): View
    {
        $kelurahan = $this->activeKelurahan();
        $pokjaList = $kelurahan
            ? Pokja::query()->where('kelurahan_id', $kelurahan->id)->orderBy('kode')->get()
            : collect();
        $rtList = $kelurahan
            ? Rt::query()->where('kelurahan_id', $kelurahan->id)->orderBy('nomor')->get()
            : collect();
        $orangList = $kelurahan
            ? Orang::query()->where('kelurahan_id', $kelurahan->id)->orderBy('nama')->limit(500)->get()
            : collect();

        return view('kegiatan.create', [
            'kelurahan' => $kelurahan,
            'pokjaList' => $pokjaList,
            'rtList' => $rtList,
            'orangList' => $orangList,
            'jenisList' => Kegiatan::daftarJenis(),
        ]);
    }

    public function store(StoreKegiatanRequest $request): RedirectResponse
    {
        $kelurahan = $this->activeKelurahan();
        if ($kelurahan === null) {
            return redirect()->route('kegiatan.index')
                ->with('error', 'Kelurahan aktif tidak ditemukan.');
        }

        $validated = $request->validated();

        $kegiatan = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null,
            'rt_id' => isset($validated['rt_id']) ? (int) $validated['rt_id'] : null,
            'nama' => $validated['nama'],
            'jenis' => $validated['jenis'],
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'] ?? null,
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'tempat' => $validated['tempat'],
            'acara' => $validated['acara'],
            'uraian' => $validated['uraian'] ?? null,
            'pimpinan_rapat_id' => isset($validated['pimpinan_rapat_id']) ? (int) $validated['pimpinan_rapat_id'] : null,
        ]);

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Kegiatan berhasil disimpan.');
    }

    public function show(Kegiatan $kegiatan): View
    {
        $kegiatan->load([
            'kelurahan',
            'pokja',
            'rt',
            'pimpinanRapat',
            'presensi.orang',
            'notulen.pembuat',
        ]);

        $orangList = Orang::query()
            ->where('kelurahan_id', $kegiatan->kelurahan_id)
            ->orderBy('nama')
            ->limit(500)
            ->get();

        return view('kegiatan.show', [
            'kegiatan' => $kegiatan,
            'orangList' => $orangList,
            'notulen' => $kegiatan->notulen ?? new Notulen(['kegiatan_id' => $kegiatan->id]),
        ]);
    }

    public function edit(Kegiatan $kegiatan): View
    {
        $pokjaList = Pokja::query()
            ->where('kelurahan_id', $kegiatan->kelurahan_id)
            ->orderBy('kode')
            ->get();
        $rtList = Rt::query()
            ->where('kelurahan_id', $kegiatan->kelurahan_id)
            ->orderBy('nomor')
            ->get();
        $orangList = Orang::query()
            ->where('kelurahan_id', $kegiatan->kelurahan_id)
            ->orderBy('nama')
            ->limit(500)
            ->get();

        return view('kegiatan.edit', [
            'kegiatan' => $kegiatan,
            'pokjaList' => $pokjaList,
            'rtList' => $rtList,
            'orangList' => $orangList,
            'jenisList' => Kegiatan::daftarJenis(),
        ]);
    }

    public function update(UpdateKegiatanRequest $request, Kegiatan $kegiatan): RedirectResponse
    {
        $validated = $request->validated();

        $kegiatan->update([
            'pokja_id' => isset($validated['pokja_id']) ? (int) $validated['pokja_id'] : null,
            'rt_id' => isset($validated['rt_id']) ? (int) $validated['rt_id'] : null,
            'nama' => $validated['nama'],
            'jenis' => $validated['jenis'],
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'] ?? null,
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'tempat' => $validated['tempat'],
            'acara' => $validated['acara'],
            'uraian' => $validated['uraian'] ?? null,
            'pimpinan_rapat_id' => isset($validated['pimpinan_rapat_id']) ? (int) $validated['pimpinan_rapat_id'] : null,
        ]);

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan): RedirectResponse
    {
        $kegiatan->delete();

        return redirect()->route('kegiatan.index')
            ->with('success', 'Kegiatan berhasil dihapus.');
    }

    public function cariOrang(Request $request): JsonResponse
    {
        $kelurahan = $this->activeKelurahan();
        $q = $request->string('q')->trim()->toString();

        $query = Orang::query()->orderBy('nama')->limit(20);
        if ($kelurahan) {
            $query->where('kelurahan_id', $kelurahan->id);
        }
        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('nama', 'like', '%'.$q.'%')
                    ->orWhere('alamat', 'like', '%'.$q.'%');
            });
        }

        $items = $query->get(['id', 'nama', 'alamat'])->map(fn (Orang $o) => [
            'id' => $o->id,
            'nama' => $o->nama,
            'alamat' => $o->alamat,
        ]);

        return response()->json($items);
    }

    public function simpanPresensi(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $peserta = $request->input('peserta', []);
        if (! is_array($peserta)) {
            return redirect()->route('kegiatan.show', $kegiatan)
                ->with('error', 'Data peserta tidak valid.');
        }

        DB::transaction(function () use ($peserta, $kegiatan, $request): void {
            $kegiatan->presensi()->delete();

            $urut = 1;
            foreach ($peserta as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $orangId = isset($row['orang_id']) && $row['orang_id'] !== '' ? (int) $row['orang_id'] : null;
                $namaManual = isset($row['nama_manual']) ? trim((string) $row['nama_manual']) : '';
                if ($orangId === null && $namaManual === '') {
                    continue;
                }

                $hadir = filter_var($row['hadir'] ?? true, FILTER_VALIDATE_BOOLEAN);

                Presensi::query()->create([
                    'kegiatan_id' => $kegiatan->id,
                    'orang_id' => $orangId,
                    'nama_manual' => $orangId === null ? $namaManual : null,
                    'alamat_manual' => $orangId === null ? ($row['alamat_manual'] ?? null) : null,
                    'jabatan_manual' => $orangId === null ? ($row['jabatan_manual'] ?? null) : null,
                    'urut' => $urut,
                    'hadir' => $hadir,
                    'keterangan' => $row['keterangan'] ?? null,
                    'oleh_user_id' => $request->user()?->id,
                ]);

                $urut++;
            }
        });

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Daftar presensi berhasil disimpan.');
    }

    public function ubahPresensi(Request $request, Kegiatan $kegiatan, Presensi $presensi): RedirectResponse
    {
        if ($presensi->kegiatan_id !== $kegiatan->id) {
            abort(404);
        }

        $hadir = filter_var($request->input('hadir', $presensi->hadir), FILTER_VALIDATE_BOOLEAN);
        $keterangan = $request->input('keterangan');

        $presensi->update([
            'hadir' => $hadir,
            'keterangan' => $keterangan !== null && $keterangan !== '' ? (string) $keterangan : null,
        ]);

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Presensi peserta diperbarui.');
    }

    public function simpanNotulen(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $validated = $request->validate([
            'macam_rapat' => ['nullable', 'string', 'max:255'],
            'jumlah_diundang' => ['nullable', 'integer', 'min:0'],
            'jumlah_hadir' => ['nullable', 'integer', 'min:0'],
            'jumlah_tidak_hadir' => ['nullable', 'integer', 'min:0'],
            'uraian_jalannya' => ['nullable', 'string'],
            'keputusan' => ['nullable', 'string'],
            'lain_lain' => ['nullable', 'string'],
            'penutup' => ['nullable', 'string'],
            'tempat_tanggal_ttd' => ['nullable', 'string', 'max:255'],
        ]);

        $hadirDariPresensi = $kegiatan->presensi()->where('hadir', true)->count();
        $tidakHadirDariPresensi = $kegiatan->presensi()->where('hadir', false)->count();

        $jumlahHadir = $request->filled('jumlah_hadir')
            ? (int) $validated['jumlah_hadir']
            : $hadirDariPresensi;

        $jumlahTidakHadir = $request->filled('jumlah_tidak_hadir')
            ? (int) $validated['jumlah_tidak_hadir']
            : $tidakHadirDariPresensi;

        $data = [
            'macam_rapat' => $validated['macam_rapat'] ?? null,
            'jumlah_diundang' => isset($validated['jumlah_diundang']) ? (int) $validated['jumlah_diundang'] : null,
            'jumlah_hadir' => $jumlahHadir,
            'jumlah_tidak_hadir' => $jumlahTidakHadir,
            'uraian_jalannya' => $validated['uraian_jalannya'] ?? null,
            'keputusan' => $validated['keputusan'] ?? null,
            'lain_lain' => $validated['lain_lain'] ?? null,
            'penutup' => $validated['penutup'] ?? null,
            'tempat_tanggal_ttd' => $validated['tempat_tanggal_ttd'] ?? null,
            'pembuat_id' => $request->user()?->id,
        ];

        Notulen::query()->updateOrCreate(
            ['kegiatan_id' => $kegiatan->id],
            $data
        );

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Notulen berhasil disimpan.');
    }

    private function activeKelurahan(): ?Kelurahan
    {
        return Kelurahan::query()->where('is_active', true)->first();
    }
}
