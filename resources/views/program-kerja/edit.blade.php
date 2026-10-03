@extends('layouts.app', [
    'title' => 'Ubah Program Kerja — Buku PKK Digital',
    'header' => 'Ubah Program Kerja',
])

@section('page_content')
    <form method="post" action="{{ route('program-kerja.update', $programKerja) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('program-kerja.partials.form', [
                    'programKerja' => $programKerja,
                    'pokjaList' => $pokjaList,
                    'buku' => $programKerja->pokja_id ? 'pokja-'.$programKerja->pokja_id : 'kelurahan',
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                <a href="{{ route('program-kerja.show', $programKerja) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
