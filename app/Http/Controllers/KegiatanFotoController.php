<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesPokjaScope;
use App\Models\Kegiatan;
use App\Models\KegiatanFoto;
use App\Support\PkkPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KegiatanFotoController extends Controller
{
    use HandlesPokjaScope;

    public function store(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $this->authorizeUnggahFoto();
        $this->authorizePokjaRecord($kegiatan->pokja_id);

        $validated = $request->validate([
            'foto' => ['required', 'array', 'min:1', 'max:12'],
            'foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], [
            'foto.required' => 'Pilih setidaknya satu foto untuk diunggah.',
            'foto.array' => 'Format unggahan foto tidak valid.',
            'foto.min' => 'Pilih setidaknya satu foto untuk diunggah.',
            'foto.max' => 'Maksimal 12 foto dapat diunggah sekaligus.',
            'foto.*.required' => 'Setiap berkas foto wajib diisi.',
            'foto.*.image' => 'Berkas harus berupa gambar.',
            'foto.*.mimes' => 'Format gambar harus JPG, JPEG, PNG, atau WEBP.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 4 MB.',
            'keterangan.max' => 'Keterangan maksimal 500 karakter.',
        ]);

        $keterangan = isset($validated['keterangan']) && $validated['keterangan'] !== ''
            ? $validated['keterangan']
            : null;
        $userId = $request->user()?->id;

        try {
            DB::transaction(function () use ($request, $kegiatan, $keterangan, $userId): void {
                $urutBerikut = (int) KegiatanFoto::query()
                    ->where('kegiatan_id', $kegiatan->id)
                    ->max('urut');

                foreach ($request->file('foto', []) as $file) {
                    if ($file === null) {
                        continue;
                    }

                    $urutBerikut++;
                    $path = $file->store('kegiatan-foto/'.$kegiatan->id, 'local');

                    KegiatanFoto::query()->create([
                        'kegiatan_id' => $kegiatan->id,
                        'nama_asli' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'keterangan' => $keterangan,
                        'urut' => $urutBerikut,
                        'uploaded_by' => $userId,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('kegiatan.show', $kegiatan)
                ->with('error', 'Foto kegiatan gagal diunggah. Silakan coba lagi.');
        }

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Foto kegiatan berhasil diunggah.');
    }

    public function destroy(KegiatanFoto $foto): RedirectResponse
    {
        $this->authorizeHapusFoto();

        $kegiatan = $foto->kegiatan;
        $this->authorizePokjaRecord($kegiatan?->pokja_id);

        try {
            DB::transaction(function () use ($foto): void {
                if (Storage::disk('local')->exists($foto->file_path)) {
                    Storage::disk('local')->delete($foto->file_path);
                }

                $foto->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('kegiatan.show', $kegiatan)
                ->with('error', 'Foto kegiatan gagal dihapus. Silakan coba lagi.');
        }

        return redirect()->route('kegiatan.show', $kegiatan)
            ->with('success', 'Foto kegiatan berhasil dihapus.');
    }

    public function berkas(KegiatanFoto $foto): StreamedResponse
    {
        $kegiatan = $foto->kegiatan;
        if ($kegiatan === null) {
            abort(404);
        }

        $this->authorizePokjaRecordRead($kegiatan->pokja_id);

        if (! Storage::disk('local')->exists($foto->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($foto->file_path);
    }

    private function authorizeUnggahFoto(): void
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        if ($user->can(PkkPermission::ISI_PRESENSI) || $user->can(PkkPermission::KELOLA_KEGIATAN)) {
            return;
        }

        abort(403);
    }

    private function authorizeHapusFoto(): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->can(PkkPermission::KELOLA_KEGIATAN)) {
            abort(403);
        }
    }
}
