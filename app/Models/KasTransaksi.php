<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class KasTransaksi extends Model
{
    use LogsActivity;

    public const JENIS_MASUK = 'masuk';

    public const JENIS_KELUAR = 'keluar';

    public const POS_TUNAI = 'tunai';

    public const POS_BANK = 'bank';

    protected $table = 'kas_transaksi';

    protected $fillable = [
        'kelurahan_id',
        'pokja_id',
        'tahun',
        'jenis',
        'pos',
        'tanggal',
        'sumber_dana',
        'uraian',
        'no_bukti',
        'jumlah',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tahun' => 'integer',
            'jumlah' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('kas_transaksi')
            ->logAll()
            ->logOnlyDirty();
    }

    protected static function booted(): void
    {
        static::saving(function (KasTransaksi $transaksi): void {
            if ($transaksi->tanggal !== null) {
                $transaksi->tahun = (int) $transaksi->tanggal->format('Y');
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

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<KasTransaksi>  $query
     * @return Builder<KasTransaksi>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }

    /**
     * @param  Builder<KasTransaksi>  $query
     * @return Builder<KasTransaksi>
     */
    public function scopeJenis(Builder $query, string $jenis): Builder
    {
        return $query->where('jenis', $jenis);
    }
}
