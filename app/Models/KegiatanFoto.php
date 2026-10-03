<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class KegiatanFoto extends Model
{
    use LogsActivity;

    protected $table = 'kegiatan_foto';

    protected $fillable = [
        'kegiatan_id',
        'nama_asli',
        'file_path',
        'keterangan',
        'urut',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'urut' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('kegiatan_foto')
            ->logAll()
            ->logOnlyDirty();
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
