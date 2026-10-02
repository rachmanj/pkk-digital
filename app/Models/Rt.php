<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rt extends Model
{
    /** @use HasFactory<\Database\Factories\RtFactory> */
    use HasFactory;

    protected $table = 'rt';

    protected $fillable = [
        'kelurahan_id',
        'nomor',
        'dasawisma',
        'ketua_orang_id',
    ];

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }
}
