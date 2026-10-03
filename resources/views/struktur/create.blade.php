@extends('layouts.app', [
    'title' => 'Tambah Pengurus — Buku PKK Digital',
    'header' => 'Tambah Pengurus',
])

@section('page_content')
    <form method="post" action="{{ route('struktur.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('struktur.partials.form', [
                    'struktur' => null,
                    'pokjaList' => $pokjaList,
                    'unit' => $unit,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('struktur.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
