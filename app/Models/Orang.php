<?php

namespace App\Models;

use Database\Factories\OrangFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orang extends Model
{
    /** @use HasFactory<OrangFactory> */
    use HasFactory;

    protected $table = 'orang';

    protected $fillable = [
        'kelurahan_id',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'status_perkawinan',
        'alamat',
        'rt_id',
        'pendidikan',
        'pekerjaan',
        'no_hp',
        'foto_path',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    protected function umur(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->tanggal_lahir === null) {
                return null;
            }

            return $this->tanggal_lahir->age;
        });
    }

    public function keanggotaan(): HasMany
    {
        return $this->hasMany(Keanggotaan::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(Rt::class);
    }

    public function isDipakaiSebagaiPimpinanKegiatan(): bool
    {
        if (! method_exists($this, 'kegiatanSebagaiPimpinan')) {
            return false;
        }

        return $this->kegiatanSebagaiPimpinan()->exists();
    }

    public function inisialNama(): string
    {
        $parts = preg_split('/\s+/', trim($this->nama), -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false || $parts === []) {
            return '?';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1));
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = mb_substr($parts[count($parts) - 1], 0, 1);

        return mb_strtoupper($first.$last);
    }
}
