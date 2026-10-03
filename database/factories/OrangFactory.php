<?php

namespace Database\Factories;

use App\Models\Kelurahan;
use App\Models\Orang;
use App\Models\Rt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Orang>
 */
class OrangFactory extends Factory
{
    protected $model = Orang::class;

    public function definition(): array
    {
        $faker = fake('id_ID');

        return [
            'kelurahan_id' => Kelurahan::factory(),
            'nama' => $faker->name(),
            'jenis_kelamin' => $faker->randomElement(['L', 'P']),
            'tempat_lahir' => $faker->city(),
            'tanggal_lahir' => $faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'status_perkawinan' => $faker->randomElement(['Menikah', 'Belum Menikah', 'Cerai Hidup', 'Cerai Mati']),
            'alamat' => 'Jl. '.$faker->streetName().' No. '.$faker->buildingNumber(),
            'rt_id' => null,
            'pendidikan' => $faker->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2']),
            'pekerjaan' => $faker->randomElement(['Ibu Rumah Tangga', 'PNS', 'Wiraswasta', 'Guru', 'Pedagang']),
            'no_hp' => $faker->numerify('08##########'),
            'foto_path' => null,
            'catatan' => $faker->optional(0.2)->sentence(),
        ];
    }

    public function forKelurahan(Kelurahan $kelurahan): static
    {
        return $this->state(fn () => [
            'kelurahan_id' => $kelurahan->id,
        ]);
    }

    public function withRt(Rt $rt): static
    {
        return $this->state(fn () => [
            'rt_id' => $rt->id,
            'kelurahan_id' => $rt->kelurahan_id,
        ]);
    }
}
