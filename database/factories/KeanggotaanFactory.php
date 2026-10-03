<?php

namespace Database\Factories;

use App\Models\Keanggotaan;
use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Pokja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keanggotaan>
 */
class KeanggotaanFactory extends Factory
{
    protected $model = Keanggotaan::class;

    public function definition(): array
    {
        $faker = fake('id_ID');
        $jenis = $faker->randomElement(Keanggotaan::JENIS_VALUES);

        return [
            'orang_id' => Orang::factory(),
            'kelurahan_id' => Kelurahan::factory(),
            'jenis' => $jenis,
            'pokja_id' => $jenis === Keanggotaan::JENIS_TP_PKK ? Pokja::factory() : null,
            'jabatan' => $jenis === Keanggotaan::JENIS_TP_PKK
                ? $faker->randomElement(['Ketua Pokja I', 'Sekretaris Pokja II', 'Anggota Pokja III'])
                : ($jenis === Keanggotaan::JENIS_KADER_UMUM ? 'Kader Umum' : 'Kader Khusus'),
            'no_registrasi' => $faker->unique()->numerify('TPPKK-####'),
            'sk_nomor' => $faker->optional()->numerify('SK/###/PKK/####'),
            'mulai' => $faker->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d'),
            'selesai' => null,
            'is_aktif' => true,
        ];
    }

    public function tpPkk(?Pokja $pokja = null): static
    {
        return $this->state(function (array $attributes) use ($pokja) {
            $pokja = $pokja ?? Pokja::factory()->create([
                'kelurahan_id' => $attributes['kelurahan_id'] ?? Kelurahan::factory(),
            ]);

            return [
                'jenis' => Keanggotaan::JENIS_TP_PKK,
                'pokja_id' => $pokja->id,
                'kelurahan_id' => $pokja->kelurahan_id,
                'jabatan' => 'Ketua Pokja '.$pokja->kode,
            ];
        });
    }

    public function forOrangKelurahan(Orang $orang): static
    {
        return $this->state(fn () => [
            'orang_id' => $orang->id,
            'kelurahan_id' => $orang->kelurahan_id,
        ]);
    }
}
