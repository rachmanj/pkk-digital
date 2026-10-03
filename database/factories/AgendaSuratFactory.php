<?php

namespace Database\Factories;

use App\Models\AgendaSurat;
use App\Models\Kelurahan;
use App\Models\Pokja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendaSurat>
 */
class AgendaSuratFactory extends Factory
{
    protected $model = AgendaSurat::class;

    public function definition(): array
    {
        $faker = fake('id_ID');
        $jenis = $faker->randomElement([AgendaSurat::JENIS_MASUK, AgendaSurat::JENIS_KELUAR]);
        $tanggalSurat = $faker->dateTimeBetween('-1 year', 'now');

        return [
            'kelurahan_id' => Kelurahan::factory(),
            'jenis' => $jenis,
            'pokja_id' => null,
            'no_urut_tahun' => 1,
            'tahun' => (int) $tanggalSurat->format('Y'),
            'tanggal_surat' => $tanggalSurat,
            'tanggal_terima' => $jenis === AgendaSurat::JENIS_MASUK ? $tanggalSurat : null,
            'no_surat' => strtoupper($faker->bothify('???/###/PKK')),
            'dari' => $jenis === AgendaSurat::JENIS_MASUK ? $faker->company() : null,
            'kepada' => $jenis === AgendaSurat::JENIS_KELUAR ? $faker->company() : null,
            'perihal' => $faker->sentence(4),
            'lampiran' => $faker->optional()->numerify('# berkas'),
            'tembusan' => $jenis === AgendaSurat::JENIS_KELUAR ? $faker->optional()->words(2, true) : null,
            'file_path' => null,
            'catatan' => null,
        ];
    }

    public function masuk(): static
    {
        return $this->state(function (array $attributes): array {
            $tanggal = $attributes['tanggal_surat'] ?? now();

            return [
                'jenis' => AgendaSurat::JENIS_MASUK,
                'tanggal_terima' => $tanggal,
                'dari' => fake('id_ID')->company(),
                'kepada' => null,
                'tembusan' => null,
            ];
        });
    }

    public function keluar(): static
    {
        return $this->state(fn (): array => [
            'jenis' => AgendaSurat::JENIS_KELUAR,
            'tanggal_terima' => null,
            'dari' => null,
            'kepada' => fake('id_ID')->company(),
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
}
