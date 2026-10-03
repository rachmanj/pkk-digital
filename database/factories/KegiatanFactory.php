<?php

namespace Database\Factories;

use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Pokja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kegiatan>
 */
class KegiatanFactory extends Factory
{
    protected $model = Kegiatan::class;

    public function definition(): array
    {
        $faker = fake('id_ID');
        $tanggal = $faker->dateTimeBetween('-6 months', 'now');

        return [
            'kelurahan_id' => Kelurahan::factory(),
            'pokja_id' => null,
            'rt_id' => null,
            'nama' => $faker->sentence(3),
            'jenis' => $faker->randomElement(Kegiatan::daftarJenis()),
            'tanggal' => $tanggal,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
            'tempat' => 'Balai '.$faker->streetName(),
            'acara' => $faker->sentence(4),
            'uraian' => $faker->optional()->paragraph(),
            'pimpinan_rapat_id' => null,
        ];
    }

    public function forKelurahan(Kelurahan $kelurahan): static
    {
        return $this->state(fn () => [
            'kelurahan_id' => $kelurahan->id,
        ]);
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

    public function denganPimpinan(Orang $orang): static
    {
        return $this->state(fn () => [
            'pimpinan_rapat_id' => $orang->id,
            'kelurahan_id' => $orang->kelurahan_id,
        ]);
    }
}
