<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\PkkPermission;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
use JeroenNoten\LaravelAdminLte\Menu\Builder;
use ReflectionProperty;

class ConfigureAdminLteMenu
{
    public function handle(BuildingMenu $event): void
    {
        $this->resetMenu($event->menu);

        $user = auth()->user();

        $event->menu->add([
            'text' => 'Dashboard',
            'url' => 'dashboard',
            'icon' => 'bi bi-speedometer2',
            'active' => ['dashboard'],
        ]);

        if ($this->can($user, PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_ANGGOTA)) {
            $event->menu->add([
                'text' => 'Data Anggota',
                'url' => 'orang',
                'icon' => 'bi bi-person-lines-fill',
                'active' => ['orang*'],
            ]);
        }

        if ($this->can($user, PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_SURAT)) {
            $event->menu->add([
                'text' => 'Agenda Surat',
                'url' => 'agenda-surat',
                'icon' => 'bi bi-journal-text',
                'active' => ['agenda-surat*'],
            ]);
        }

        if ($this->can($user, PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_KEGIATAN, PkkPermission::ISI_PRESENSI)) {
            $event->menu->add([
                'text' => 'Kegiatan',
                'url' => 'kegiatan',
                'icon' => 'bi bi-calendar-event',
                'active' => ['kegiatan*'],
            ]);
        }

        if ($this->can($user, PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_BUKU_TAMU)) {
            $event->menu->add([
                'text' => 'Buku Tamu',
                'url' => 'buku-tamu',
                'icon' => 'bi bi-book',
                'active' => ['buku-tamu*'],
            ]);
        }

        if ($this->can($user, PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_BUKU_KUNJUNGAN)) {
            $event->menu->add([
                'text' => 'Buku Kunjungan',
                'url' => 'buku-kunjungan',
                'icon' => 'bi bi-geo-alt',
                'active' => ['buku-kunjungan*'],
            ]);
        }

        if ($user instanceof User && $user->can(PkkPermission::KELOLA_PENGGUNA)) {
            $event->menu->add([
                'text' => 'Pengguna',
                'url' => 'pengguna',
                'icon' => 'bi bi-people',
                'active' => ['pengguna*'],
            ]);
        }

        if ($user instanceof User && $user->can(PkkPermission::LIHAT_AUDIT)) {
            $event->menu->add([
                'text' => 'Audit',
                'url' => 'audit',
                'icon' => 'bi bi-shield-check',
                'active' => ['audit*'],
            ]);
        }
    }

    private function can(?User $user, string ...$permissions): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function resetMenu(Builder $builder): void
    {
        $rawMenu = new ReflectionProperty($builder, 'rawMenu');
        $rawMenu->setValue($builder, []);

        $shouldCompile = new ReflectionProperty($builder, 'shouldCompile');
        $shouldCompile->setValue($builder, true);
    }
}
