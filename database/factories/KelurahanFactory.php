<?php

namespace Database\Factories;

use App\Models\Kelurahan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelurahan>
 */
class KelurahanFactory extends Factory
{
    protected $model = Kelurahan::class;

    public function definition(): array
    {
        $faker = fake('id_ID');

        return [
            'nama' => $faker->streetName(),
            'kecamatan' => $faker->citySuffix(),
            'kota' => $faker->city(),
            'provinsi' => $faker->state(),
            'kode' => strtoupper($faker->unique()->bothify('???###')),
            'is_active' => true,
        ];
    }
}
