<?php

namespace Database\Factories;

use App\Models\BukuTamu;
use App\Models\Kelurahan;
use App\Models\Pokja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BukuTamu>
 */
class BukuTamuFactory extends Factory
{
    protected $model = BukuTamu::class;

    public function definition(): array
    {
        $faker = fake('id_ID');
        $tanggal = $faker->dateTimeBetween('-1 year', 'now');

        return [
            'kelurahan_id' => Kelurahan::factory(),
            'pokja_id' => null,
            'tahun' => (int) $tanggal->format('Y'),
            'no_urut_tahun' => 1,
            'tanggal' => $tanggal,
            'nama_tamu' => $faker->name(),
            'alamat' => $faker->optional()->address(),
            'keperluan' => $faker->optional()->sentence(3),
            'tujuan' => $faker->optional()->words(3, true),
            'keterangan' => null,
        ];
    }

    public function untukPokja(?Pokja $pokja = null): static
    {
        return $this->state(function () use ($pokja): array {
            $pokja ??= Pokja::factory()->create();

            return [
                'kelurahan_id' => $pokja->kelurahan_id,
                'pokja_id' => $pokja->id,
            ];
        });
    }
}
