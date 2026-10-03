<?php

namespace App\Models;

use Database\Factories\KeanggotaanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Keanggotaan extends Model
{
    /** @use HasFactory<KeanggotaanFactory> */
    use HasFactory, LogsActivity;

    public const JENIS_TP_PKK = 'tp_pkk';

    public const JENIS_KADER_UMUM = 'kader_umum';

    public const JENIS_KADER_KHUSUS = 'kader_khusus';

    /** @var list<string> */
    public const JENIS_VALUES = [
        self::JENIS_TP_PKK,
        self::JENIS_KADER_UMUM,
        self::JENIS_KADER_KHUSUS,
    ];

    protected $table = 'keanggotaan';

    protected $fillable = [
        'orang_id',
        'kelurahan_id',
        'jenis',
        'pokja_id',
        'jabatan',
        'no_registrasi',
        'sk_nomor',
        'mulai',
        'selesai',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'selesai' => 'date',
            'is_aktif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('keanggotaan')
            ->logAll()
            ->logOnlyDirty();
    }

    public function orang(): BelongsTo
    {
        return $this->belongsTo(Orang::class);
    }

    public function pokja(): BelongsTo
    {
        return $this->belongsTo(Pokja::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    /**
     * @param  Builder<Keanggotaan>  $query
     * @return Builder<Keanggotaan>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true);
    }

    public function labelJenis(): string
    {
        return match ($this->jenis) {
            self::JENIS_TP_PKK => 'Dalam Keanggotaan TP PKK',
            self::JENIS_KADER_UMUM => 'Kader Umum',
            self::JENIS_KADER_KHUSUS => 'Kader Khusus',
            default => $this->jenis,
        };
    }
}
