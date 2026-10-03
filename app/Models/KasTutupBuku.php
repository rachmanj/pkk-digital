<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class KasTutupBuku extends Model
{
    use LogsActivity;

    protected $table = 'kas_tutup_buku';

    protected $fillable = [
        'kelurahan_id',
        'pokja_id',
        'tahun',
        'tanggal_tutup',
        'sisa_bank',
        'sisa_tunai',
        'total',
        'catatan',
        'nama_ketua',
        'nama_bendahara',
        'ditutup_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'tanggal_tutup' => 'date',
            'sisa_bank' => 'decimal:2',
            'sisa_tunai' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('kas_tutup_buku')
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

    public function penutup(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }

    /**
     * @param  Builder<KasTutupBuku>  $query
     * @return Builder<KasTutupBuku>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }
}
