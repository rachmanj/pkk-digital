<?php

namespace App\Support;

use App\Models\Kegiatan;
use App\Models\Kelurahan;
use App\Models\Pokja;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class KegiatanUnit
{
    /**
     * @return array{pokja_id: ?int, pelaksana: ?string}
     */
    public static function parseNilaiUnit(?string $unit): array
    {
        $unit = $unit !== null ? trim($unit) : '';

        if ($unit === '') {
            return ['pokja_id' => null, 'pelaksana' => null];
        }

        if ($unit === Kegiatan::PELAKSANA_KETUA) {
            return ['pokja_id' => null, 'pelaksana' => Kegiatan::PELAKSANA_KETUA];
        }

        if ($unit === Kegiatan::PELAKSANA_SEKRETARIS) {
            return ['pokja_id' => null, 'pelaksana' => Kegiatan::PELAKSANA_SEKRETARIS];
        }

        if (str_starts_with($unit, 'pokja-')) {
            $id = (int) substr($unit, 6);

            return ['pokja_id' => $id > 0 ? $id : null, 'pelaksana' => null];
        }

        return ['pokja_id' => null, 'pelaksana' => null];
    }

    /**
     * @return list<string>
     */
    public static function daftarNilaiForm(): array
    {
        $nilai = ['', Kegiatan::PELAKSANA_KETUA, Kegiatan::PELAKSANA_SEKRETARIS];

        return $nilai;
    }

    public static function nilaiFormDariKegiatan(Kegiatan $kegiatan): string
    {
        if ($kegiatan->pelaksana === Kegiatan::PELAKSANA_KETUA) {
            return Kegiatan::PELAKSANA_KETUA;
        }

        if ($kegiatan->pelaksana === Kegiatan::PELAKSANA_SEKRETARIS) {
            return Kegiatan::PELAKSANA_SEKRETARIS;
        }

        if ($kegiatan->pokja_id !== null) {
            return 'pokja-'.$kegiatan->pokja_id;
        }

        return '';
    }

    public static function unitValid(?string $unit, ?Kelurahan $kelurahan): bool
    {
        $unit = trim($unit ?? '');
        if ($unit === '') {
            return true;
        }

        if ($unit === Kegiatan::PELAKSANA_KETUA || $unit === Kegiatan::PELAKSANA_SEKRETARIS) {
            return true;
        }

        if (str_starts_with($unit, 'pokja-')) {
            $id = (int) substr($unit, 6);
            if ($id <= 0) {
                return false;
            }
            if ($kelurahan === null) {
                return false;
            }

            return Pokja::query()
                ->whereKey($id)
                ->where('kelurahan_id', $kelurahan->id)
                ->exists();
        }

        return false;
    }

    public static function resolveUnitFilter(Request $request): ?string
    {
        $unit = $request->input('unit');
        if ($unit !== null && $unit !== '') {
            return (string) $unit;
        }

        $pokjaId = $request->input('pokja_id');
        if ($pokjaId !== null && $pokjaId !== '') {
            return 'pokja-'.(int) $pokjaId;
        }

        return null;
    }

    public static function pokjaIdUntukScope(?string $unit): ?int
    {
        if ($unit === null || $unit === '') {
            return null;
        }

        if (str_starts_with($unit, 'pokja-')) {
            return (int) substr($unit, 6);
        }

        return null;
    }

    /**
     * @param  Builder<Kegiatan>  $query
     */
    public static function terapkanFilterUnit(Builder $query, ?string $unit): void
    {
        if ($unit === null || $unit === '') {
            return;
        }

        if ($unit === Kegiatan::PELAKSANA_KETUA || $unit === Kegiatan::PELAKSANA_SEKRETARIS) {
            $query->where('pelaksana', $unit);

            return;
        }

        if (str_starts_with($unit, 'pokja-')) {
            $query->where('pokja_id', (int) substr($unit, 6));
        }
    }

    public static function judulBukuKegiatan(?string $unit, ?Pokja $pokja = null): string
    {
        if ($unit === Kegiatan::PELAKSANA_KETUA) {
            return 'Buku Kegiatan Ketua';
        }

        if ($unit === Kegiatan::PELAKSANA_SEKRETARIS) {
            return 'Buku Kegiatan Sekretaris';
        }

        if ($unit !== null && str_starts_with($unit, 'pokja-') && $pokja !== null) {
            return 'Buku Kegiatan Pokja '.$pokja->kode;
        }

        return 'Buku Kegiatan';
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    public static function unitDariFilter(array $filter): ?string
    {
        $unit = isset($filter['unit']) && $filter['unit'] !== ''
            ? (string) $filter['unit']
            : null;

        if ($unit !== null) {
            return $unit;
        }

        if (isset($filter['pokja_id']) && $filter['pokja_id'] !== null && $filter['pokja_id'] !== '') {
            return 'pokja-'.(int) $filter['pokja_id'];
        }

        if (isset($filter['buku']) && is_string($filter['buku']) && str_starts_with($filter['buku'], 'pokja-')) {
            return $filter['buku'];
        }

        return null;
    }
}
