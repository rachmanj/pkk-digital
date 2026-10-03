<?php

namespace App\Models;

use Database\Factories\KelurahanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelurahan extends Model
{
    /** @use HasFactory<KelurahanFactory> */
    use HasFactory;

    protected $table = 'kelurahan';

    protected $fillable = [
        'nama',
        'kecamatan',
        'kota',
        'provinsi',
        'kode',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pokja(): HasMany
    {
        return $this->hasMany(Pokja::class);
    }

    public function rt(): HasMany
    {
        return $this->hasMany(Rt::class);
    }

    public function kasTransaksi(): HasMany
    {
        return $this->hasMany(KasTransaksi::class);
    }
}
