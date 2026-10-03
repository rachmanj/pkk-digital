<?php

namespace App\Models;

use Database\Factories\AgendaSuratFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaSurat extends Model
{
    /** @use HasFactory<AgendaSuratFactory> */
    use HasFactory;

    public const JENIS_MASUK = 'masuk';

    public const JENIS_KELUAR = 'keluar';

    protected $table = 'agenda_surat';

    protected $fillable = [
        'kelurahan_id',
        'jenis',
        'pokja_id',
        'no_urut_tahun',
        'tanggal_surat',
        'tanggal_terima',
        'no_surat',
        'dari',
        'kepada',
        'perihal',
        'lampiran',
        'tembusan',
        'file_path',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_terima' => 'date',
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

    public function disposisi(): HasMany
    {
        return $this->hasMany(Disposisi::class);
    }

    /**
     * @param  Builder<AgendaSurat>  $query
     * @return Builder<AgendaSurat>
     */
    public function scopeMasuk(Builder $query): Builder
    {
        return $query->where('jenis', self::JENIS_MASUK);
    }

    /**
     * @param  Builder<AgendaSurat>  $query
     * @return Builder<AgendaSurat>
     */
    public function scopeKeluar(Builder $query): Builder
    {
        return $query->where('jenis', self::JENIS_KELUAR);
    }

    /**
     * @param  Builder<AgendaSurat>  $query
     * @return Builder<AgendaSurat>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->whereYear('tanggal_surat', $tahun);
    }

    public static function nomorUrutBerikutnya(
        int $kelurahanId,
        string $jenis,
        ?int $pokjaId,
        int $tahun
    ): int {
        $query = static::query()
            ->where('kelurahan_id', $kelurahanId)
            ->where('jenis', $jenis)
            ->whereYear('tanggal_surat', $tahun);

        if ($pokjaId === null) {
            $query->whereNull('pokja_id');
        } else {
            $query->where('pokja_id', $pokjaId);
        }

        $max = $query->max('no_urut_tahun');

        return ($max ?? 0) + 1;
    }

    public function diteruskanKepadaRingkas(): string
    {
        $this->loadMissing(['disposisi.pokja', 'disposisi.user']);

        $labels = $this->disposisi
            ->map(function (Disposisi $disposisi): ?string {
                if ($disposisi->pokja !== null) {
                    return 'Pokja '.$disposisi->pokja->kode;
                }

                if ($disposisi->user !== null) {
                    return $disposisi->user->name;
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            return '—';
        }

        return $labels->implode(', ');
    }

    public function statusTindakLanjut(): string
    {
        $this->loadMissing('disposisi');

        if ($this->disposisi->isEmpty()) {
            return 'belum';
        }

        if ($this->disposisi->every(fn (Disposisi $d) => $d->status === Disposisi::STATUS_SELESAI)) {
            return 'selesai';
        }

        if ($this->disposisi->contains(fn (Disposisi $d) => $d->status === Disposisi::STATUS_PROSES)) {
            return 'proses';
        }

        return 'baru';
    }
}
