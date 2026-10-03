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
            PkkPermission::KELOLA_KAS,
            PkkPermission::LIHAT_KAS,
            PkkPermission::KELOLA_INVENTARIS,
            PkkPermission::LIHAT_INVENTARIS,
        ];

        $ketuaPokjaPermissions = array_values(array_filter(
            $sekretarisPermissions,
            fn (string $permission): bool => ! in_array($permission, [
                PkkPermission::KELOLA_KAS,
                PkkPermission::KELOLA_INVENTARIS,
            ], true)
        ));

        $this->syncRolePermissions('superadmin', PkkPermission::all());
        $this->syncRolePermissions('sekretaris', $sekretarisPermissions);
        $this->syncRolePermissions('bendahara', [
            PkkPermission::KELOLA_KAS,
            PkkPermission::LIHAT_KAS,
            PkkPermission::LIHAT_INVENTARIS,
        ]);
        $this->syncRolePermissions('ketua', [
            PkkPermission::LIHAT_BUKU,
            PkkPermission::VERIFIKASI_BUKU,
            PkkPermission::LIHAT_AUDIT,
            PkkPermission::LIHAT_KAS,
            PkkPermission::LIHAT_INVENTARIS,
        ]);
        $this->syncRolePermissions('ketua_pokja', $ketuaPokjaPermissions);
        $this->syncRolePermissions('kader', [
            PkkPermission::LIHAT_BUKU,
            PkkPermission::ISI_PRESENSI,
            PkkPermission::KELOLA_BUKU_TAMU,
            PkkPermission::LIHAT_INVENTARIS,
        ]);

        $password = env('ADMIN_PASSWORD', 'password');
        $pokjaI = $this->pokjaPertama();

        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@pkk.test',
                'name' => 'Administrator',
                'role' => 'superadmin',
                'pokja_id' => null,
            ],
            [
                'username' => 'sekretaris',
                'email' => 'sekretaris@pkk.test',
                'name' => 'Sekretaris PKK',
                'role' => 'sekretaris',
                'pokja_id' => null,
            ],
            [
                'username' => 'bendahara',
                'email' => 'bendahara@pkk.test',
                'name' => 'Bendahara TP PKK',
                'role' => 'bendahara',
                'pokja_id' => null,
            ],
            [
                'username' => 'ketua',
                'email' => 'ketua@pkk.test',
                'name' => 'Ketua TP PKK',
                'role' => 'ketua',
                'pokja_id' => null,
            ],
            [
                'username' => 'ketua-pokja',
                'email' => 'ketua-pokja@pkk.test',
                'name' => 'Ketua Pokja I',
                'role' => 'ketua_pokja',
                'pokja_id' => $pokjaI?->id,
            ],
            [
                'username' => 'kader',
                'email' => 'kader@pkk.test',
                'name' => 'Kader PKK',
                'role' => 'kader',
                'pokja_id' => null,
            ],
        ];

        foreach ($users as $definition) {
            $user = User::query()->where('username', $definition['username'])->first();

            if ($user === null) {
                $user = User::query()->create([
                    'username' => $definition['username'],
                    'email' => $definition['email'],
                    'name' => $definition['name'],
                    'password' => $password,
                    'pokja_id' => $definition['pokja_id'],
                ]);
            }

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
