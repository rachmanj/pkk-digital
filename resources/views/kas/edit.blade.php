@extends('layouts.app', [
    'title' => 'Ubah Transaksi Kas — Buku PKK Digital',
    'header' => 'Ubah Transaksi Kas',
])

@section('page_content')
    <form method="post" action="{{ route('kas.update', $transaksi) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('kas.partials.form', [
                    'transaksi' => $transaksi,
                    'pokjaList' => $pokjaList,
                    'buku' => $transaksi->pokja_id ? 'pokja-'.$transaksi->pokja_id : 'kelurahan',
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                <a href="{{ route('kas.show', $transaksi) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
