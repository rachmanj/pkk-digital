<?php

namespace Database\Factories;

use App\Models\BukuKunjungan;
use App\Models\Kelurahan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BukuKunjungan>
 */
class BukuKunjunganFactory extends Factory
{
    protected $model = BukuKunjungan::class;

    public function definition(): array
    {
        $faker = fake('id_ID');
        $tanggal = $faker->dateTimeBetween('-1 year', 'now');

        return [
            'kelurahan_id' => Kelurahan::factory(),
            'tahun' => (int) $tanggal->format('Y'),
            'no_urut_tahun' => 1,
            'tanggal' => $tanggal,
            'orang_id' => null,
            'nama' => $faker->name(),
            'jabatan' => $faker->optional()->jobTitle(),
            'lokasi_kunjungan' => $faker->optional()->city(),
            'jenis_kegiatan' => $faker->optional()->words(2, true),
            'keterangan' => null,
        ];
    }
}
