@extends('layouts.app', [
    'title' => 'Ubah Pengguna — Buku PKK Digital',
    'header' => 'Ubah Pengguna',
])

@section('page_content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('pengguna.update', $user) }}">
                @csrf
                @method('PUT')
                @include('pengguna._form', ['user' => $user])
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('pengguna.index') }}" class="btn btn-link">Batal</a>
            </form>
        </div>
    </div>
@endsection
