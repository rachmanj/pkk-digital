@extends('layouts.app', [
    'title' => 'Tambah Inventaris — Buku PKK Digital',
    'header' => 'Tambah Inventaris',
])

@section('page_content')
    <form method="post" action="{{ route('inventaris.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('inventaris.partials.form', [
                    'inventarisBarang' => null,
                    'pokjaList' => $pokjaList,
                    'buku' => $buku,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('inventaris.index', ['buku' => $buku]) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
