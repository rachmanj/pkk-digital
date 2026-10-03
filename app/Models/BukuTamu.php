<?php

namespace App\Models;

use Database\Factories\BukuTamuFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class BukuTamu extends Model
{
    /** @use HasFactory<BukuTamuFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'buku_tamu';

    protected $fillable = [
        'kelurahan_id',
        'pokja_id',
        'tahun',
        'no_urut_tahun',
        'tanggal',
        'nama_tamu',
        'alamat',
        'keperluan',
        'tujuan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tahun' => 'integer',
            'no_urut_tahun' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('buku_tamu')
            ->logAll()
            ->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::saving(function (BukuTamu $bukuTamu): void {
            if ($bukuTamu->tanggal !== null) {
                $bukuTamu->tahun = (int) $bukuTamu->tanggal->format('Y');
            }
        });
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function pokja(): BelongsTo
    {
        return $this->belongsTo(Pokja::class);
    }

    /**
     * @param  Builder<BukuTamu>  $query
     * @return Builder<BukuTamu>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }

    public static function nomorUrutBerikutnya(
        int $kelurahanId,
        ?int $pokjaId,
        int $tahun
    ): int {
        $query = static::query()
            ->where('kelurahan_id', $kelurahanId)
            ->where('tahun', $tahun);

        if ($pokjaId === null) {
            $query->whereNull('pokja_id');
        } else {
            $query->where('pokja_id', $pokjaId);
        }

        $max = $query->max('no_urut_tahun');

        return ($max ?? 0) + 1;
    }
}
