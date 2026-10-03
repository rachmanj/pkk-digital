@extends('layouts.app', [
    'title' => 'Ubah Agenda Surat — Buku PKK Digital',
    'header' => 'Ubah Agenda Surat',
])

@section('page_content')
    <form method="post" action="{{ route('agenda-surat.update', $agendaSurat) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('agenda-surat.partials.form', [
                    'agendaSurat' => $agendaSurat,
                    'pokjaList' => $pokjaList,
                    'jenis' => $agendaSurat->jenis,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('agenda-surat.show', $agendaSurat) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
