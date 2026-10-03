<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class InventarisBarang extends Model
{
    use LogsActivity;

    public const KONDISI_BAIK = 'baik';

    public const KONDISI_RUSAK_RINGAN = 'rusak_ringan';

    public const KONDISI_RUSAK_BERAT = 'rusak_berat';

    protected $table = 'inventaris_barang';

    protected $fillable = [
        'kelurahan_id',
        'pokja_id',
        'tahun',
        'nama_barang',
        'asal_barang',
        'tanggal_terima',
        'jumlah',
        'tempat_penyimpanan',
        'kondisi',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_terima' => 'date',
            'tahun' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inventaris_barang')
            ->logAll()
            ->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::saving(function (InventarisBarang $barang): void {
            if ($barang->tanggal_terima !== null) {
                $barang->tahun = (int) $barang->tanggal_terima->format('Y');
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

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<InventarisBarang>  $query
     * @return Builder<InventarisBarang>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }

    public function labelKondisi(): string
    {
        return self::labelKondisiUntuk($this->kondisi);
    }

    public static function labelKondisiUntuk(string $kondisi): string
    {
        return match ($kondisi) {
            self::KONDISI_BAIK => 'Baik',
            self::KONDISI_RUSAK_RINGAN => 'Rusak Ringan',
            self::KONDISI_RUSAK_BERAT => 'Rusak Berat',
            default => $kondisi,
        };
    }

    /**
     * @return list<string>
     */
    public static function kondisiNilai(): array
    {
        return [
            self::KONDISI_BAIK,
            self::KONDISI_RUSAK_RINGAN,
            self::KONDISI_RUSAK_BERAT,
        ];
    }
}
