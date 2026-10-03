@extends('layouts.app', [
    'title' => 'Ubah Inventaris — Buku PKK Digital',
    'header' => 'Ubah Inventaris',
])

@section('page_content')
    <form method="post" action="{{ route('inventaris.update', $inventarisBarang) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('inventaris.partials.form', [
                    'inventarisBarang' => $inventarisBarang,
                    'pokjaList' => $pokjaList,
                    'buku' => $inventarisBarang->pokja_id ? 'pokja-'.$inventarisBarang->pokja_id : 'kelurahan',
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('inventaris.show', $inventarisBarang) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
