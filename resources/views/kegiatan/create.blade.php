@extends('layouts.app', [
    'title' => 'Tambah Kegiatan — Buku PKK Digital',
    'header' => 'Tambah Kegiatan',
])

@section('page_content')
    <form method="post" action="{{ route('kegiatan.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('kegiatan.partials.form', [
                    'kegiatan' => null,
                    'pokjaList' => $pokjaList,
                    'rtList' => $rtList,
                    'orangList' => $orangList,
                    'jenisList' => $jenisList,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('kegiatan.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
