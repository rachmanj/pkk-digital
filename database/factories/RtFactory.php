<?php

namespace Database\Factories;

use App\Models\Kelurahan;
use App\Models\Rt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rt>
 */
class RtFactory extends Factory
{
    protected $model = Rt::class;

    public function definition(): array
    {
        $faker = fake('id_ID');

        return [
            'kelurahan_id' => Kelurahan::factory(),
            'nomor' => (string) $faker->numberBetween(1, 99),
            'dasawisma' => $faker->optional()->words(2, true),
            'ketua_orang_id' => null,
        ];
    }
}
