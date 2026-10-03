@extends('layouts.app', [
    'title' => 'Tambah Anggota — Buku PKK Digital',
    'header' => 'Tambah Anggota',
])

@section('page_content')
    <form method="post" action="{{ route('orang.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('orang.partials.form', [
                    'orang' => null,
                    'pokjaList' => $pokjaList,
                    'rtList' => $rtList,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('orang.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
