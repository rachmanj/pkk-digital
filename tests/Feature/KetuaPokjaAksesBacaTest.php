<?php

namespace Tests\Feature;

use App\Models\AgendaSurat;
use App\Models\Kelurahan;
use App\Models\Kegiatan;
use App\Models\KegiatanFoto;
use App\Models\Pokja;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KetuaPokjaAksesBacaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{kelurahan: Kelurahan, pokja: Pokja, pokjaIi: Pokja, ketuaPokja: User}
     */
    private function seedKetuaPokjaI(): array
    {
        $this->seed(MasterSeeder::class);
        $kelurahan = Kelurahan::query()->where('kode', 'GSI')->firstOrFail();
        $pokjaI = Pokja::query()->where('kelurahan_id', $kelurahan->id)->where('kode', 'I')->firstOrFail();
        $pokjaIi = Pokja::query()->where('kelurahan_id', $kelurahan->id)->where('kode', 'II')->firstOrFail();

        $ketuaPokja = $this->userForRole('ketua_pokja', [
            'email' => 'ketua-pokja-baca@pkk.test',
            'pokja_id' => $pokjaI->id,
        ]);

        return [
            'kelurahan' => $kelurahan,
            'pokja' => $pokjaI,
            'pokjaIi' => $pokjaIi,
            'ketuaPokja' => $ketuaPokja,
        ];
    }

    public function test_ketua_pokja_i_membuka_surat_tingkat_kelurahan_dan_pokja_i_403_pokja_ii(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaI, 'pokjaIi' => $pokjaIi, 'ketuaPokja' => $ketuaPokja] = $this->seedKetuaPokjaI();

        $suratKelurahan = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tahun' => 2026,
            'tanggal_surat' => '2026-01-10',
            'tanggal_terima' => '2026-01-11',
            'no_surat' => 'KEL/001',
            'dari' => 'Dinas',
            'perihal' => 'Tingkat kelurahan',
        ]);

        $suratI = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaI->id,
            'no_urut_tahun' => 1,
            'tahun' => 2026,
            'tanggal_surat' => '2026-05-01',
            'tanggal_terima' => '2026-05-02',
            'no_surat' => 'POKJA-I/1',
            'dari' => 'A',
            'perihal' => 'Pokja I',
        ]);

        $suratIi = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaIi->id,
            'no_urut_tahun' => 1,
            'tahun' => 2026,
            'tanggal_surat' => '2026-05-03',
            'tanggal_terima' => '2026-05-04',
            'no_surat' => 'POKJA-II/1',
            'dari' => 'B',
            'perihal' => 'Pokja II',
        ]);

        $this->actingAs($ketuaPokja)->get(route('agenda-surat.show', $suratKelurahan))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('agenda-surat.show', $suratI))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('agenda-surat.show', $suratIi))->assertForbidden();
    }

    public function test_ketua_pokja_i_membuka_kegiatan_tingkat_kelurahan_dan_pokja_i_403_pokja_ii(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaI, 'pokjaIi' => $pokjaIi, 'ketuaPokja' => $ketuaPokja] = $this->seedKetuaPokjaI();

        $kegiatanKelurahan = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'nama' => 'Rapat kelurahan',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-03-01',
            'tempat' => 'Balai',
            'acara' => 'Koordinasi',
        ]);

        $kegiatanI = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaI->id,
            'nama' => 'Pembinaan Pokja I',
            'jenis' => Kegiatan::JENIS_PEMBINAAN,
            'tanggal' => '2026-04-01',
            'tempat' => 'Aula',
            'acara' => 'Pembinaan',
        ]);

        $kegiatanIi = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaIi->id,
            'nama' => 'Kegiatan Pokja II',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-04-02',
            'tempat' => 'Aula',
            'acara' => 'Rapat',
        ]);

        $this->actingAs($ketuaPokja)->get(route('kegiatan.show', $kegiatanKelurahan))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('kegiatan.show', $kegiatanI))->assertOk();
        $this->actingAs($ketuaPokja)->get(route('kegiatan.show', $kegiatanIi))->assertForbidden();
    }

    public function test_ketua_pokja_i_membuka_berkas_foto_kegiatan_tingkat_kelurahan(): void
    {
        Storage::fake('local');
        ['kelurahan' => $kelurahan, 'ketuaPokja' => $ketuaPokja] = $this->seedKetuaPokjaI();

        $kegiatan = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => null,
            'nama' => 'Dokumentasi kelurahan',
            'jenis' => Kegiatan::JENIS_PEMBINAAN,
            'tanggal' => '2026-05-01',
            'tempat' => 'Lapangan',
            'acara' => 'Senam',
        ]);

        $path = 'kegiatan-foto/'.$kegiatan->id.'/uji.jpg';
        Storage::disk('local')->put($path, 'isi-gambar-palsu');

        $foto = KegiatanFoto::query()->create([
            'kegiatan_id' => $kegiatan->id,
            'nama_asli' => 'uji.jpg',
            'file_path' => $path,
            'urut' => 1,
            'uploaded_by' => $ketuaPokja->id,
        ]);

        $this->actingAs($ketuaPokja)->get(route('kegiatan-foto.berkas', $foto))->assertOk();
    }

    public function test_ketua_pokja_i_cetak_buku_kelurahan_dan_403_buku_pokja_ii(): void
    {
        ['pokjaIi' => $pokjaIi, 'ketuaPokja' => $ketuaPokja] = $this->seedKetuaPokjaI();

        $this->actingAs($ketuaPokja)->get(route('cetak.show', [
            'buku' => 'agenda_surat_masuk',
            'tahun' => 2026,
        ]))->assertOk();

        $this->actingAs($ketuaPokja)->get(route('cetak.show', [
            'buku' => 'agenda_surat_masuk',
            'tahun' => 2026,
            'pokja_id' => $pokjaIi->id,
        ]))->assertForbidden();
    }

    public function test_sekretaris_membuka_surat_dan_kegiatan_semua_pokja(): void
    {
        ['kelurahan' => $kelurahan, 'pokja' => $pokjaI, 'pokjaIi' => $pokjaIi] = $this->seedKetuaPokjaI();
        $sekretaris = $this->userForRole('sekretaris', ['email' => 'sekretaris-pokja-baca@pkk.test']);

        $suratKelurahan = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tahun' => 2026,
            'tanggal_surat' => '2026-02-01',
            'tanggal_terima' => '2026-02-02',
            'no_surat' => 'SEC/KEL',
            'dari' => 'X',
            'perihal' => 'Kelurahan',
        ]);

        $suratIi = AgendaSurat::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'pokja_id' => $pokjaIi->id,
            'no_urut_tahun' => 1,
            'tahun' => 2026,
            'tanggal_surat' => '2026-02-03',
            'tanggal_terima' => '2026-02-04',
            'no_surat' => 'SEC/II',
            'dari' => 'Y',
            'perihal' => 'Pokja II',
        ]);

        $kegiatanIi = Kegiatan::query()->create([
            'kelurahan_id' => $kelurahan->id,
            'pokja_id' => $pokjaIi->id,
            'nama' => 'Kegiatan II',
            'jenis' => Kegiatan::JENIS_RAPAT,
            'tanggal' => '2026-06-01',
            'tempat' => 'Aula',
            'acara' => 'Rapat',
        ]);

        $this->actingAs($sekretaris)->get(route('agenda-surat.show', $suratKelurahan))->assertOk();
        $this->actingAs($sekretaris)->get(route('agenda-surat.show', $suratIi))->assertOk();
        $this->actingAs($sekretaris)->get(route('kegiatan.show', $kegiatanIi))->assertOk();

        $this->actingAs($sekretaris)->get(route('cetak.show', [
            'buku' => 'agenda_surat_masuk',
            'tahun' => 2026,
            'pokja_id' => $pokjaI->id,
        ]))->assertOk();
    }
}
