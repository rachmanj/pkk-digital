<?php

namespace App\Models;

use Database\Factories\KegiatanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kegiatan extends Model
{
    /** @use HasFactory<KegiatanFactory> */
    use HasFactory;

    public const JENIS_PERTEMUAN = 'pertemuan';

    public const JENIS_PEMBINAAN = 'pembinaan';

    public const JENIS_PELATIHAN = 'pelatihan';

    public const JENIS_POSYANDU = 'posyandu';

    public const JENIS_ARISAN = 'arisan';

    public const JENIS_RAPAT = 'rapat';

    public const JENIS_LAIN_LAIN = 'lain_lain';

    /**
     * @return list<string>
     */
    public static function daftarJenis(): array
    {
        return [
            self::JENIS_PERTEMUAN,
            self::JENIS_PEMBINAAN,
            self::JENIS_PELATIHAN,
            self::JENIS_POSYANDU,
            self::JENIS_ARISAN,
            self::JENIS_RAPAT,
            self::JENIS_LAIN_LAIN,
        ];
    }

    protected $table = 'kegiatan';

    protected $fillable = [
        'kelurahan_id',
        'pokja_id',
        'rt_id',
        'nama',
        'jenis',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'tempat',
        'acara',
        'uraian',
        'pimpinan_rapat_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function pokja(): BelongsTo
    {
        return $this->belongsTo(Pokja::class);
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function pimpinanRapat(): BelongsTo
    {
        return $this->belongsTo(Orang::class, 'pimpinan_rapat_id');
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class)->orderBy('urut');
    }

    public function notulen(): HasOne
    {
        return $this->hasOne(Notulen::class);
    }

    /**
     * @param  Builder<Kegiatan>  $query
     * @return Builder<Kegiatan>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->whereYear('tanggal', $tahun);
    }

    /**
     * @param  Builder<Kegiatan>  $query
     * @return Builder<Kegiatan>
     */
    public function scopeBulan(Builder $query, int $bulan): Builder
    {
        return $query->whereMonth('tanggal', $bulan);
    }

    public function hadirCount(): int
    {
        return $this->presensi()->where('hadir', true)->count();
    }

    public function labelJenis(): string
    {
        return match ($this->jenis) {
            self::JENIS_PERTEMUAN => 'Pertemuan',
            self::JENIS_PEMBINAAN => 'Pembinaan',
            self::JENIS_PELATIHAN => 'Pelatihan',
            self::JENIS_POSYANDU => 'Posyandu',
            self::JENIS_ARISAN => 'Arisan',
            self::JENIS_RAPAT => 'Rapat',
            self::JENIS_LAIN_LAIN => 'Lain-lain',
            default => $this->jenis,
        };
    }
}
