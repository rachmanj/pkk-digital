<?php

namespace Database\Seeders;

use App\Models\Kelurahan;
use App\Models\Pokja;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $kelurahan = Kelurahan::updateOrCreate(
            ['kode' => 'GSI'],
            [
                'nama' => 'Gunung Sari Ilir',
                'kecamatan' => 'Balikpapan Tengah',
                'kota' => 'Balikpapan',
                'provinsi' => 'Kalimantan Timur',
                'is_active' => true,
            ]
        );

        foreach (['I', 'II', 'III', 'IV'] as $kode) {
            Pokja::updateOrCreate(
                [
                    'kelurahan_id' => $kelurahan->id,
                    'kode' => $kode,
                ],
                [
                    'nama' => 'Pokja '.$kode,
                ]
            );
        }
    }
}
