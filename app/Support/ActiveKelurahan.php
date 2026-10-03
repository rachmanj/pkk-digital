<?php

namespace App\Support;

use App\Models\Kelurahan;
use App\Models\User;
use Illuminate\Support\Collection;

class ActiveKelurahan
{
    public const SESSION_KEY = 'active_kelurahan_id';

    public function resolve(?User $user = null): ?Kelurahan
    {
        $user = $user ?? auth()->user();

        if ($user instanceof User && $user->hasRole('superadmin')) {
            $sessionId = session(self::SESSION_KEY);
            if ($sessionId !== null && $sessionId !== '') {
                $fromSession = Kelurahan::query()->find((int) $sessionId);
                if ($fromSession !== null) {
                    return $fromSession;
                }
            }
        }

        if ($user instanceof User && $user->pokja_id !== null) {
            $user->loadMissing('pokja.kelurahan');
            if ($user->pokja?->kelurahan !== null) {
                return $user->pokja->kelurahan;
            }
        }

        return Kelurahan::query()->where('is_active', true)->first()
            ?? Kelurahan::query()->orderBy('id')->first();
    }

    public function setForSuperadmin(int $kelurahanId, User $user): bool
    {
        if (! $user->hasRole('superadmin')) {
            return false;
        }

        if (! Kelurahan::query()->whereKey($kelurahanId)->exists()) {
            return false;
        }

        session([self::SESSION_KEY => $kelurahanId]);

        return true;
    }

    /**
     * @return Collection<int, Kelurahan>
     */
    public function daftarUntukPemilih(): Collection
    {
        return Kelurahan::query()->orderBy('nama')->get();
    }
}
