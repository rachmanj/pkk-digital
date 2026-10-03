<?php

namespace App\Models;

use Database\Factories\PokjaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pokja extends Model
{
    /** @use HasFactory<PokjaFactory> */
    use HasFactory;

    protected $table = 'pokja';

    protected $fillable = [
        'kelurahan_id',
        'kode',
        'nama',
    ];

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function kasTransaksi(): HasMany
    {
        return $this->hasMany(KasTransaksi::class);
    }
}
