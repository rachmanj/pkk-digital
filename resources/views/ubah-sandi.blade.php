@extends('layouts.app', [
    'title' => 'Ubah Sandi — Buku PKK Digital',
    'header' => 'Ubah Sandi',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('ubah-sandi.update') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="password_saat_ini">Kata sandi saat ini</label>
                    <input type="password" name="password_saat_ini" id="password_saat_ini"
                        class="form-control @error('password_saat_ini') is-invalid @enderror"
                        autocomplete="current-password" required>
                    @error('password_saat_ini')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Kata sandi baru</label>
                    <input type="password" name="password" id="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="new-password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Ulangi kata sandi baru</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="form-control" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('dashboard') }}" class="btn btn-link">Batal</a>
            </form>
        </div>
    </div>
@endsection
