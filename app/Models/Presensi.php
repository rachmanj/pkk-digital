<?php

namespace App\Models;

use Database\Factories\PresensiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Presensi extends Model
{
    /** @use HasFactory<PresensiFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'presensi';

    protected $fillable = [
        'kegiatan_id',
        'orang_id',
        'nama_manual',
        'alamat_manual',
        'jabatan_manual',
        'urut',
        'hadir',
        'keterangan',
        'oleh_user_id',
    ];

    protected function casts(): array
    {
        return [
            'hadir' => 'boolean',
            'urut' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('presensi')
            ->logAll()
            ->logOnlyDirty();
    }

    protected function namaTampil(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->relationLoaded('orang') || $this->orang_id !== null) {
                $this->loadMissing('orang');
                if ($this->orang !== null) {
                    return $this->orang->nama;
                }
            }

            return (string) ($this->nama_manual ?? '');
        });
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function orang(): BelongsTo
    {
        return $this->belongsTo(Orang::class);
    }

    /**
     * @param  Builder<Presensi>  $query
     * @return Builder<Presensi>
     */
    public function scopeHadir(Builder $query): Builder
    {
        return $query->where('hadir', true);
    }
}
