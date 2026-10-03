<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Support\PokjaScope;
use Illuminate\Database\Eloquent\Builder;

trait HandlesPokjaScope
{
    protected function ketuaPokjaPokjaId(): ?int
    {
        $user = auth()->user();

        return PokjaScope::ketuaPokjaId($user instanceof User ? $user : null);
    }

    protected function authorizePokjaRecordRead(?int $pokjaId): void
    {
        if (! PokjaScope::ketuaMayReadRecord($this->ketuaPokjaPokjaId(), $pokjaId)) {
            abort(403);
        }
    }

    protected function authorizePokjaRecord(?int $pokjaId): void
    {
        if (! PokjaScope::ketuaMayWriteRecord($this->ketuaPokjaPokjaId(), $pokjaId)) {
            abort(403);
        }
    }

    protected function authorizePokjaBukuFilter(?int $pokjaIdFromBuku): void
    {
        if (! PokjaScope::ketuaMayAccessBukuFilter($this->ketuaPokjaPokjaId(), $pokjaIdFromBuku)) {
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

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applyKetuaPokjaKegiatanIndexScope(Builder $query, ?int $requestedPokjaId): void
    {
        PokjaScope::applyKetuaPokjaKegiatanIndexScope($query, $this->ketuaPokjaPokjaId(), $requestedPokjaId);
    }
}
