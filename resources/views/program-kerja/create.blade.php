@extends('layouts.app', [
    'title' => 'Tambah Program Kerja — Buku PKK Digital',
    'header' => 'Tambah Program Kerja',
])

@section('page_content')
    <form method="post" action="{{ route('program-kerja.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('program-kerja.partials.form', [
                    'programKerja' => null,
                    'pokjaList' => $pokjaList,
                    'buku' => $buku,
                    'tahun' => $tahun,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('program-kerja.index', ['buku' => $buku, 'tahun' => $tahun]) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
