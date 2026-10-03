<div class="mb-3">
    <label class="form-label" for="name">Nama</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $user?->name) }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label class="form-label" for="username">Nama pengguna</label>
    <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror"
        value="{{ old('username', $user?->username) }}" required autocomplete="username">
    @error('username')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
        value="{{ old('email', $user?->email) }}" required>
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label class="form-label" for="password">Kata sandi @if ($user)
            <span class="text-muted">(kosongkan jika tidak diubah)</span>
        @endif
    </label>
    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
        @if (! $user) required @endif autocomplete="new-password">
    @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label class="form-label" for="role">Peran</label>
    <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
        <option value="">— Pilih —</option>
        @foreach ($roles as $role)
            <option value="{{ $role->name }}" @selected(old('role', $user?->roles->first()?->name) === $role->name)>
                {{ $role->name }}
            </option>
        @endforeach
    </select>
    @error('role')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label class="form-label" for="pokja_id">Pokja (untuk ketua_pokja)</label>
    <select name="pokja_id" id="pokja_id" class="form-select @error('pokja_id') is-invalid @enderror">
        <option value="">— Tidak ada —</option>
        @foreach ($pokjaList as $pokja)
            <option value="{{ $pokja->id }}" @selected((string) old('pokja_id', $user?->pokja_id) === (string) $pokja->id)>
                Pokja {{ $pokja->kode }}
            </option>
        @endforeach
    </select>
    @error('pokja_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
