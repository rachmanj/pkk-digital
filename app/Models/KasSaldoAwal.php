<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class KasSaldoAwal extends Model
{
    use LogsActivity;

    public const POS_TUNAI = 'tunai';

    public const POS_BANK = 'bank';

    protected $table = 'kas_saldo_awal';

    protected $fillable = [
        'kelurahan_id',
        'pokja_id',
        'tahun',
        'pos',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'jumlah' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('kas_saldo_awal')
            ->logAll()
            ->logOnlyDirty();
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
     * @param  Builder<KasSaldoAwal>  $query
     * @return Builder<KasSaldoAwal>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }
}
