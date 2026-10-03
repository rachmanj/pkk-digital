<?php

namespace Database\Factories;

use App\Models\Kegiatan;
use App\Models\Notulen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notulen>
 */
class NotulenFactory extends Factory
{
    protected $model = Notulen::class;

    public function definition(): array
    {
        $faker = fake('id_ID');

        return [
            'kegiatan_id' => Kegiatan::factory(),
            'macam_rapat' => $faker->randomElement(['Rapat Koordinasi', 'Rapat Bulanan', 'Rapat Pokja']),
            'jumlah_diundang' => $faker->numberBetween(10, 30),
            'jumlah_hadir' => null,
            'jumlah_tidak_hadir' => null,
            'uraian_jalannya' => $faker->paragraphs(2, true),
            'keputusan' => $faker->paragraph(),
            'lain_lain' => $faker->optional()->sentence(),
            'penutup' => 'Demikian notulen ini dibuat untuk dipergunakan sebagaimana mestinya.',
            'pembuat_id' => null,
            'tempat_tanggal_ttd' => $faker->city().', '.now()->format('d F Y'),
        ];
    }

    public function untukKegiatan(Kegiatan $kegiatan): static
    {
        return $this->state(fn () => [
            'kegiatan_id' => $kegiatan->id,
        ]);
    }
}
