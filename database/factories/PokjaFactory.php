<?php

namespace Database\Factories;

use App\Models\Kelurahan;
use App\Models\Pokja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pokja>
 */
class PokjaFactory extends Factory
{
    protected $model = Pokja::class;

    public function definition(): array
    {
        static $kodeIndex = 0;
        $kodes = ['I', 'II', 'III', 'IV'];
        $kode = $kodes[$kodeIndex % count($kodes)];
        $kodeIndex++;

        return [
            'kelurahan_id' => Kelurahan::factory(),
            'kode' => $kode,
            'nama' => 'Pokja '.$kode,
        ];
    }
}
