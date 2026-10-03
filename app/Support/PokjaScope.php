<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Satu sumber aturan pembatasan Pokja untuk peran ketua_pokja.
 *
 * BACA (index, show, berkas, cetak/ekspor per buku):
 * - pokja_id NULL = buku tingkat kelurahan → boleh diakses.
 * - pokja_id sama dengan pokja pengguna → boleh diakses.
 * - pokja_id milik Pokja lain → ditolak (403).
 *
 * Peran lain (superadmin, sekretaris, ketua, kader) tidak dibatasi oleh kelas ini.
 *
 * TULIS (buat, ubah, hapus):
 * - ketua_pokja hanya boleh pada data yang pokja_id tepat sama dengan pokja miliknya.
 * - Buku tingkat kelurahan (pokja_id NULL) tetap ditolak untuk tulis; jangan longgarkan di sini.
 */
final class PokjaScope
{
    public static function ketuaPokjaId(?User $user): ?int
    {
        if ($user === null || ! $user->hasRole('ketua_pokja')) {
            return null;
        }

        return $user->pokja_id;
    }

    public static function ketuaMayReadRecord(?int $ketuaPokjaId, ?int $recordPokjaId): bool
    {
        if ($ketuaPokjaId === null) {
            return true;
        }

        if ($recordPokjaId === null) {
            return true;
        }

        return $recordPokjaId === $ketuaPokjaId;
    }

    public static function ketuaMayWriteRecord(?int $ketuaPokjaId, ?int $recordPokjaId): bool
    {
        if ($ketuaPokjaId === null) {
            return true;
        }

        return $recordPokjaId === $ketuaPokjaId;
    }

    public static function ketuaMayAccessBukuFilter(?int $ketuaPokjaId, ?int $bukuPokjaId): bool
    {
        if ($ketuaPokjaId === null) {
            return true;
        }

        if ($bukuPokjaId === null) {
            return true;
        }

        return $bukuPokjaId === $ketuaPokjaId;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public static function applyKetuaPokjaKegiatanIndexScope(Builder $query, ?int $ketuaPokjaId, ?int $requestedPokjaId): void
    {
        if ($ketuaPokjaId === null) {
            if ($requestedPokjaId !== null) {
                $query->where('pokja_id', $requestedPokjaId);
            }

            return;
        }

        if ($requestedPokjaId !== null && $requestedPokjaId !== $ketuaPokjaId) {
            abort(403);
        }

        if ($requestedPokjaId !== null) {
            $query->where('pokja_id', $requestedPokjaId);

            return;
        }

        $query->where(function (Builder $q) use ($ketuaPokjaId): void {
            $q->whereNull('pokja_id')->orWhere('pokja_id', $ketuaPokjaId);
        });
    }
}
