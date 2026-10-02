<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pokja extends Model
{
    /** @use HasFactory<\Database\Factories\PokjaFactory> */
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
}
