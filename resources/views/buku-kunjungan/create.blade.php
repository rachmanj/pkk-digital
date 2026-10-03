@extends('layouts.app', [
    'title' => 'Tambah Buku Kunjungan — Buku PKK Digital',
    'header' => 'Tambah Buku Kunjungan',
])

@section('page_content')
    <form method="post" action="{{ route('buku-kunjungan.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('buku-kunjungan.partials.form', [
                    'bukuKunjungan' => null,
                    'orangList' => $orangList,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('buku-kunjungan.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
