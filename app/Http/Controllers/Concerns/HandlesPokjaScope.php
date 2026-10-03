<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;

trait HandlesPokjaScope
{
    protected function ketuaPokjaPokjaId(): ?int
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->hasRole('ketua_pokja')) {
            return null;
        }

        return $user->pokja_id;
    }

    protected function authorizePokjaRecord(?int $pokjaId): void
    {
        $expected = $this->ketuaPokjaPokjaId();
        if ($expected === null) {
            return;
        }

        if ($pokjaId !== $expected) {
            abort(403);
        }
    }

    protected function authorizePokjaBukuFilter(?int $pokjaIdFromBuku): void
    {
        $expected = $this->ketuaPokjaPokjaId();
        if ($expected === null) {
            return;
        }

        if ($pokjaIdFromBuku === null || $pokjaIdFromBuku !== $expected) {
            abort(403);
        }
    }

    protected function pokjaIdFromBukuParam(string $buku): ?int
    {
        if (str_starts_with($buku, 'pokja-')) {
            return (int) substr($buku, 6);
        }

        return null;
    }

    protected function defaultBukuForKetuaPokja(string $fallback = 'kelurahan'): string
    {
        $pokjaId = $this->ketuaPokjaPokjaId();
        if ($pokjaId === null) {
            return $fallback;
        }

        return 'pokja-'.$pokjaId;
    }

    protected function pokjaIdForKetuaPokjaWrite(?int $pokjaId): ?int
    {
        $expected = $this->ketuaPokjaPokjaId();
        if ($expected === null) {
            return $pokjaId;
        }

        return $expected;
    }
}
