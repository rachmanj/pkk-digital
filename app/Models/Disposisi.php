<?php

namespace App\Models;

use Database\Factories\DisposisiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Disposisi extends Model
{
    /** @use HasFactory<DisposisiFactory> */
    use HasFactory, LogsActivity;

    public const STATUS_BARU = 'baru';

    public const STATUS_PROSES = 'proses';

    public const STATUS_SELESAI = 'selesai';

    protected $table = 'disposisi';

    protected $fillable = [
        'agenda_surat_id',
        'pokja_id',
        'user_id',
        'instruksi',
        'status',
        'tenggat',
        'selesai_at',
        'oleh_user_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('disposisi')
            ->logAll()
            ->logOnlyDirty();
    }

    protected function casts(): array
    {
        return [
            'tenggat' => 'date',
            'selesai_at' => 'datetime',
        ];
    }

    public function agendaSurat(): BelongsTo
    {
        return $this->belongsTo(AgendaSurat::class);
    }

    public function pokja(): BelongsTo
    {
        return $this->belongsTo(Pokja::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function olehUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh_user_id');
    }

    /**
     * @param  Builder<Disposisi>  $query
     * @return Builder<Disposisi>
     */
    public function scopeBelumSelesai(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_SELESAI);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function terlambat(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->status === self::STATUS_SELESAI) {
                return false;
            }

            if ($this->tenggat === null) {
                return false;
            }

            return $this->tenggat->isPast();
        });
    }
}
