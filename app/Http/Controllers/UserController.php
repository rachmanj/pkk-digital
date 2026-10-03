<?php

namespace App\Http\Controllers;

use App\Models\Pokja;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with(['roles', 'pokja'])
            ->orderBy('name')
            ->paginate(20);

        return view('pengguna.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('pengguna.create', [
            'roles' => $this->roleOptions(),
            'pokjaList' => $this->pokjaList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'pokja_id' => $validated['pokja_id'] ?? null,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('pengguna.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $pengguna): View
    {
        return view('pengguna.edit', [
            'user' => $pengguna,
            'roles' => $this->roleOptions(),
            'pokjaList' => $this->pokjaList(),
        ]);
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $validated = $this->validateUser($request, $pengguna);

        $pengguna->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'pokja_id' => $validated['pokja_id'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $pengguna->password = Hash::make($validated['password']);
        }

        $pengguna->save();
        $pengguna->syncRoles([$validated['role']]);

        return redirect()->route('pengguna.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $existing = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($existing !== null) {
            $emailRule = $emailRule->ignore($existing->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'password' => [$existing === null ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in($this->roleNames())],
            'pokja_id' => ['nullable', 'integer', 'exists:pokja,id'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function roleNames(): array
    {
        return Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Role>
     */
    private function roleOptions()
    {
        return Role::query()->where('guard_name', 'web')->orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Pokja>
     */
    private function pokjaList()
    {
        return Pokja::query()->orderBy('kode')->get();
    }
}
