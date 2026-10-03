@extends('layouts.app', [
    'title' => 'Ubah Pengurus — Buku PKK Digital',
    'header' => 'Ubah Pengurus',
])

@section('page_content')
    <form method="post" action="{{ route('struktur.update', $struktur) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('struktur.partials.form', [
                    'struktur' => $struktur,
                    'pokjaList' => $pokjaList,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('struktur.show', $struktur) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
