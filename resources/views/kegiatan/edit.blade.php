@extends('layouts.app', [
    'title' => 'Ubah Kegiatan — Buku PKK Digital',
    'header' => 'Ubah Kegiatan',
])

@section('page_content')
    <form method="post" action="{{ route('kegiatan.update', $kegiatan) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('kegiatan.partials.form', [
                    'kegiatan' => $kegiatan,
                    'pokjaList' => $pokjaList,
                    'rtList' => $rtList,
                    'orangList' => $orangList,
                    'jenisList' => $jenisList,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('kegiatan.show', $kegiatan) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
