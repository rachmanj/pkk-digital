@extends('layouts.app', [
    'title' => 'Ubah Buku Kunjungan — Buku PKK Digital',
    'header' => 'Ubah Buku Kunjungan',
])

@section('page_content')
    <form method="post" action="{{ route('buku-kunjungan.update', $bukuKunjungan) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('buku-kunjungan.partials.form', [
                    'bukuKunjungan' => $bukuKunjungan,
                    'orangList' => $orangList,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('buku-kunjungan.show', $bukuKunjungan) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
