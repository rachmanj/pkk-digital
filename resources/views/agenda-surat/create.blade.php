@extends('layouts.app', [
    'title' => 'Tambah Agenda Surat — Buku PKK Digital',
    'header' => 'Tambah Agenda Surat',
])

@section('page_content')
    <form method="post" action="{{ route('agenda-surat.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('agenda-surat.partials.form', [
                    'agendaSurat' => null,
                    'pokjaList' => $pokjaList,
                    'jenis' => $jenis,
                    'buku' => $buku,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('agenda-surat.index', ['jenis' => $jenis, 'buku' => $buku]) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
