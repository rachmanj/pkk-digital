<?php

namespace App\Listeners;

use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
use JeroenNoten\LaravelAdminLte\Menu\Builder;
use ReflectionProperty;

class ConfigureAdminLteMenu
{
    public function handle(BuildingMenu $event): void
    {
        $this->resetMenu($event->menu);

        $event->menu->add([
            'text' => 'Dashboard',
            'url' => 'dashboard',
            'icon' => 'bi bi-speedometer2',
            'active' => ['dashboard'],
        ]);

        $event->menu->add([
            'text' => 'Data Anggota',
            'url' => 'orang',
            'icon' => 'bi bi-person-lines-fill',
            'active' => ['orang*'],
        ]);

        $event->menu->add([
            'text' => 'Agenda Surat',
            'url' => 'agenda-surat',
            'icon' => 'bi bi-journal-text',
            'active' => ['agenda-surat*'],
        ]);

        $event->menu->add([
            'text' => 'Kegiatan',
            'url' => 'kegiatan',
            'icon' => 'bi bi-calendar-event',
            'active' => ['kegiatan*'],
        ]);
    }

    private function resetMenu(Builder $builder): void
    {
        $rawMenu = new ReflectionProperty($builder, 'rawMenu');
        $rawMenu->setValue($builder, []);

        $shouldCompile = new ReflectionProperty($builder, 'shouldCompile');
        $shouldCompile->setValue($builder, true);
    }
}
