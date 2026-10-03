@extends('layouts.app', [
    'title' => 'Tambah Transaksi Kas — Buku PKK Digital',
    'header' => 'Tambah Transaksi Kas',
])

@section('page_content')
    <form method="post" action="{{ route('kas.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('kas.partials.form', [
                    'transaksi' => null,
                    'pokjaList' => $pokjaList,
                    'buku' => $buku,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('kas.index', ['buku' => $buku]) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
