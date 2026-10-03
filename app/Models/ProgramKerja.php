<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ProgramKerja extends Model
{
    use LogsActivity;

    public const TANDA_CENTANG = '✓';

    protected $table = 'program_kerja';

    protected $fillable = [
        'kelurahan_id',
        'pokja_id',
        'tahun',
        'kode',
        'program',
        'kegiatan',
        'tanggal_kegiatan',
        'tujuan',
        'sasaran',
        'tempat',
        'sumber_dana',
        'keterangan',
        'bulan_rencana',
        'bulan_pelaksanaan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'tanggal_kegiatan' => 'date',
            'bulan_rencana' => 'array',
            'bulan_pelaksanaan' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('program_kerja')
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

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kegiatanTertaut(): HasMany
    {
        return $this->hasMany(Kegiatan::class, 'program_kerja_id');
    }

    /**
     * @return list<int>
     */
    public static function daftarBulan(): array
    {
        return range(1, 12);
    }

    /**
     * @param  list<int|string>|null  $bulan
     * @return list<int>
     */
    public static function normalisasiBulan(?array $bulan): array
    {
        if ($bulan === null) {
            return [];
        }

        $hasil = [];
        foreach ($bulan as $nilai) {
            $n = (int) $nilai;
            if ($n >= 1 && $n <= 12) {
                $hasil[$n] = $n;
            }
        }

        ksort($hasil);

        return array_values($hasil);
    }

    public function bulanRencanaTerpilih(int $bulan): bool
    {
        return in_array($bulan, self::normalisasiBulan($this->bulan_rencana), true);
    }

    public function bulanPelaksanaanManualTerpilih(int $bulan): bool
    {
        return in_array($bulan, self::normalisasiBulan($this->bulan_pelaksanaan), true);
    }

    /**
     * @return list<int>
     */
    public function bulanPelaksanaanDariKegiatan(): array
    {
        $daftar = $this->relationLoaded('kegiatanTertaut')
            ? $this->kegiatanTertaut
            : $this->kegiatanTertaut()->get();

        $bulan = [];
        foreach ($daftar as $kegiatan) {
            if ($kegiatan->tanggal === null) {
                continue;
            }
            $n = (int) $kegiatan->tanggal->format('n');
            $bulan[$n] = $n;
        }
        ksort($bulan);

        return array_values($bulan);
    }

    /**
     * @return array<int, 'manual'|'kegiatan'|'keduanya'>
     */
    public function sumberBulanPelaksanaan(): array
    {
        $manual = self::normalisasiBulan($this->bulan_pelaksanaan);
        $dariKegiatan = $this->bulanPelaksanaanDariKegiatan();

        $hasil = [];
        foreach (self::daftarBulan() as $bulan) {
            $adaManual = in_array($bulan, $manual, true);
            $adaKegiatan = in_array($bulan, $dariKegiatan, true);
            if ($adaManual && $adaKegiatan) {
                $hasil[$bulan] = 'keduanya';
            } elseif ($adaManual) {
                $hasil[$bulan] = 'manual';
            } elseif ($adaKegiatan) {
                $hasil[$bulan] = 'kegiatan';
            }
        }

        return $hasil;
    }

    public function bulanPelaksanaanGabunganTerpilih(int $bulan): bool
    {
        return array_key_exists($bulan, $this->sumberBulanPelaksanaan());
    }

    /**
     * @param  Builder<ProgramKerja>  $query
     * @return Builder<ProgramKerja>
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->where('tahun', $tahun);
    }

    public function labelUnit(): string
    {
        if ($this->pokja_id !== null) {
            $kode = $this->relationLoaded('pokja')
                ? ($this->pokja?->kode ?? '?')
                : ($this->pokja()->value('kode') ?? '?');

            return 'Pokja '.$kode;
        }

        if ($this->relationLoaded('kelurahan') && $this->kelurahan !== null) {
            return $this->kelurahan->nama;
        }

        return $this->kelurahan()->value('nama') ?? 'Kelurahan';
    }

    public function nomorTampilan(int $urutan): string
    {
        if ($this->kode !== null && $this->kode !== '') {
            return $this->kode;
        }

        return (string) $urutan;
    }
}
