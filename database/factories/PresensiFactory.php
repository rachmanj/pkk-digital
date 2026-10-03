<?php

namespace Database\Factories;

use App\Models\Kegiatan;
use App\Models\Orang;
use App\Models\Presensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Presensi>
 */
class PresensiFactory extends Factory
{
    protected $model = Presensi::class;

    public function definition(): array
    {
        return [
            'kegiatan_id' => Kegiatan::factory(),
            'orang_id' => null,
            'nama_manual' => fake('id_ID')->name(),
            'alamat_manual' => fake('id_ID')->address(),
            'jabatan_manual' => fake('id_ID')->jobTitle(),
            'urut' => 1,
            'hadir' => true,
            'keterangan' => null,
            'oleh_user_id' => null,
        ];
    }

    public function untukKegiatan(Kegiatan $kegiatan, int $urut = 1): static
    {
        return $this->state(fn () => [
            'kegiatan_id' => $kegiatan->id,
            'urut' => $urut,
        ]);
    }

    public function untukOrang(Orang $orang): static
    {
        return $this->state(fn () => [
            'orang_id' => $orang->id,
            'nama_manual' => null,
            'alamat_manual' => null,
            'jabatan_manual' => null,
        ]);
    }

    public function tidakHadir(): static
    {
        return $this->state(fn () => [
            'hadir' => false,
        ]);
    }
}
