<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class StrukturPengurus extends Model
{
    use LogsActivity;

    public const UNIT_TP_PKK = 'tp_pkk';

    public const UNIT_POKJA = 'pokja';

    public const UNIT_LBS = 'lbs';

    public const UNIT_PHBS = 'phbs';

    protected $table = 'struktur_pengurus';

    protected $fillable = [
        'kelurahan_id',
        'unit',
        'pokja_id',
        'rt',
        'jabatan',
        'nama',
        'urutan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('struktur_pengurus')
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * @return list<string>
     */
    public static function unitNilai(): array
    {
        return [
            self::UNIT_TP_PKK,
            self::UNIT_POKJA,
            self::UNIT_LBS,
            self::UNIT_PHBS,
        ];
    }

    public static function labelUnit(string $unit): string
    {
        return match ($unit) {
            self::UNIT_TP_PKK => 'TP PKK Kelurahan',
            self::UNIT_POKJA => 'Pokja',
            self::UNIT_LBS => 'Lingkungan Binaan Sehat (LBS)',
            self::UNIT_PHBS => 'Perilaku Hidup Bersih dan Sehat (PHBS)',
            default => $unit,
        };
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
     * @param  Builder<StrukturPengurus>  $query
     * @return Builder<StrukturPengurus>
     */
    public function scopeUnit(Builder $query, string $unit): Builder
    {
        return $query->where('unit', $unit);
    }

    /**
     * @param  Builder<StrukturPengurus>  $query
     * @return Builder<StrukturPengurus>
     */
    public function scopeUrut(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }

    /**
     * @param  Collection<int, StrukturPengurus>  $items
     * @return Collection<string, Collection<int, StrukturPengurus>>
     */
    public static function kelompokkanPerJabatan(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (StrukturPengurus $row): string => mb_strtoupper(trim($row->jabatan)))
            ->map(fn (Collection $group) => $group->sortBy(['urutan', 'nama'])->values());
    }

    /**
     * @param  Collection<int, StrukturPengurus>  $items
     * @return Collection<int|string, Collection<int, StrukturPengurus>>
     */
    public static function kelompokkanPerPokja(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (StrukturPengurus $row): int|string => $row->pokja_id ?? 'tanpa_pokja')
            ->map(fn (Collection $group) => $group->sortBy(['urutan', 'nama'])->values());
    }
}
