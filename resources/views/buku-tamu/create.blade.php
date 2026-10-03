@extends('layouts.app', [
    'title' => 'Tambah Buku Tamu — Buku PKK Digital',
    'header' => 'Tambah Buku Tamu',
])

@section('page_content')
    <form method="post" action="{{ route('buku-tamu.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('buku-tamu.partials.form', [
                    'bukuTamu' => null,
                    'pokjaList' => $pokjaList,
                    'buku' => $buku,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('buku-tamu.index', ['buku' => $buku]) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
