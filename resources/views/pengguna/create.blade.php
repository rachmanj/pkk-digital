@extends('layouts.app', [
    'title' => 'Tambah Pengguna — Buku PKK Digital',
    'header' => 'Tambah Pengguna',
])

@section('page_content')
    <div class="card">
        <div class="card-body">
            <form method="post" action="{{ route('pengguna.store') }}">
                @csrf
                @include('pengguna._form', ['user' => null])
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('pengguna.index') }}" class="btn btn-link">Batal</a>
            </form>
        </div>
    </div>
@endsection
