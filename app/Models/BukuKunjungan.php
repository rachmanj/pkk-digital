<?php

namespace App\Models;

use Database\Factories\BukuKunjunganFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class BukuKunjungan extends Model
{
    /** @use HasFactory<BukuKunjunganFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'buku_kunjungan';

    protected $fillable = [
        'kelurahan_id',
        'tahun',
        'no_urut_tahun',
        'tanggal',
        'orang_id',
        'nama',
        'jabatan',
        'lokasi_kunjungan',
        'jenis_kegiatan',
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
            ->logAll()
            ->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::saving(function (BukuKunjungan $bukuKunjungan): void {
            if ($bukuKunjungan->tanggal !== null) {
                $bukuKunjungan->tahun = (int) $bukuKunjungan->tanggal->format('Y');
            }
        });
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function orang(): BelongsTo
    {
        return $this->belongsTo(Orang::class);
    }

    /**
     * @param  Builder<BukuKunjungan>  $query
     * @return Builder<BukuKunjungan>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }

    public static function nomorUrutBerikutnya(int $kelurahanId, int $tahun): int
    {
        $max = static::query()
            ->where('kelurahan_id', $kelurahanId)
            ->where('tahun', $tahun)
            ->max('no_urut_tahun');

        return ($max ?? 0) + 1;
    }
}
