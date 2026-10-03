<?php

namespace Database\Seeders;

use App\Models\Kelurahan;
use App\Models\Pokja;
use App\Models\User;
use App\Support\PkkPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PkkPermission::all() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $sekretarisPermissions = [
            PkkPermission::KELOLA_ANGGOTA,
            PkkPermission::KELOLA_SURAT,
            PkkPermission::KELOLA_KEGIATAN,
            PkkPermission::ISI_PRESENSI,
            PkkPermission::KELOLA_BUKU_TAMU,
            PkkPermission::KELOLA_BUKU_KUNJUNGAN,
            PkkPermission::LIHAT_BUKU,
        ];

        $this->syncRolePermissions('superadmin', PkkPermission::all());
        $this->syncRolePermissions('sekretaris', $sekretarisPermissions);
        $this->syncRolePermissions('ketua', [
            PkkPermission::LIHAT_BUKU,
            PkkPermission::VERIFIKASI_BUKU,
            PkkPermission::LIHAT_AUDIT,
        ]);
        $this->syncRolePermissions('ketua_pokja', $sekretarisPermissions);
        $this->syncRolePermissions('kader', [
            PkkPermission::LIHAT_BUKU,
            PkkPermission::ISI_PRESENSI,
            PkkPermission::KELOLA_BUKU_TAMU,
        ]);

        $password = env('ADMIN_PASSWORD', 'password');
        $pokjaI = $this->pokjaPertama();

        $users = [
            [
                'email' => 'admin@pkk.test',
                'name' => 'Administrator',
                'role' => 'superadmin',
                'pokja_id' => null,
            ],
            [
                'email' => 'sekretaris@pkk.test',
                'name' => 'Sekretaris PKK',
                'role' => 'sekretaris',
                'pokja_id' => null,
            ],
            [
                'email' => 'ketua@pkk.test',
                'name' => 'Ketua TP PKK',
                'role' => 'ketua',
                'pokja_id' => null,
            ],
            [
                'email' => 'ketua-pokja@pkk.test',
                'name' => 'Ketua Pokja I',
                'role' => 'ketua_pokja',
                'pokja_id' => $pokjaI?->id,
            ],
            [
                'email' => 'kader@pkk.test',
                'name' => 'Kader PKK',
                'role' => 'kader',
                'pokja_id' => null,
            ],
        ];

        foreach ($users as $definition) {
            $user = User::updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => $password,
                    'pokja_id' => $definition['pokja_id'],
                ]
            );

            $user->syncRoles([$definition['role']]);
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function syncRolePermissions(string $roleName, array $permissions): void
    {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);
    }

    private function pokjaPertama(): ?Pokja
    {
        $kelurahan = Kelurahan::query()->where('kode', 'GSI')->first();
        if ($kelurahan === null) {
            return null;
        }

        return Pokja::query()
            ->where('kelurahan_id', $kelurahan->id)
            ->where('kode', 'I')
            ->first();
    }
}
