@extends('layouts.app', [
    'title' => 'Ubah Buku Tamu — Buku PKK Digital',
    'header' => 'Ubah Buku Tamu',
])

@section('page_content')
    <form method="post" action="{{ route('buku-tamu.update', $bukuTamu) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('buku-tamu.partials.form', [
                    'bukuTamu' => $bukuTamu,
                    'pokjaList' => $pokjaList,
                    'buku' => $bukuTamu->pokja_id ? 'pokja-'.$bukuTamu->pokja_id : 'kelurahan',
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('buku-tamu.show', $bukuTamu) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
