<?php

namespace App\Models;

use Database\Factories\NotulenFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notulen extends Model
{
    /** @use HasFactory<NotulenFactory> */
    use HasFactory;

    protected $table = 'notulen';

    protected $fillable = [
        'kegiatan_id',
        'macam_rapat',
        'jumlah_diundang',
        'jumlah_hadir',
        'jumlah_tidak_hadir',
        'uraian_jalannya',
        'keputusan',
        'lain_lain',
        'penutup',
        'pembuat_id',
        'tempat_tanggal_ttd',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_diundang' => 'integer',
            'jumlah_hadir' => 'integer',
            'jumlah_tidak_hadir' => 'integer',
        ];
    }

    protected function jumlahHadirAktual(): Attribute
    {
        return Attribute::get(function (): int {
            $this->loadMissing('kegiatan.presensi');
            $kegiatan = $this->kegiatan;
            if ($kegiatan === null) {
                return 0;
            }

            return $kegiatan->presensi->where('hadir', true)->count();
        });
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembuat_id');
    }
}
