<?php

namespace Database\Factories;

use App\Models\AgendaSurat;
use App\Models\Disposisi;
use App\Models\Pokja;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disposisi>
 */
class DisposisiFactory extends Factory
{
    protected $model = Disposisi::class;

    public function definition(): array
    {
        return [
            'agenda_surat_id' => AgendaSurat::factory()->masuk(),
            'pokja_id' => Pokja::factory(),
            'user_id' => null,
            'instruksi' => fake('id_ID')->sentence(),
            'status' => Disposisi::STATUS_BARU,
            'tenggat' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'selesai_at' => null,
            'oleh_user_id' => null,
        ];
    }

    public function selesai(?User $oleh = null): static
    {
        return $this->state(function () use ($oleh): array {
            $oleh ??= User::factory()->create();

            return [
                'status' => Disposisi::STATUS_SELESAI,
                'selesai_at' => now(),
                'oleh_user_id' => $oleh->id,
            ];
        });
    }
}
