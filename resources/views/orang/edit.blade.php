@extends('layouts.app', [
    'title' => 'Ubah Anggota — Buku PKK Digital',
    'header' => 'Ubah Anggota',
])

@section('page_content')
    <form method="post" action="{{ route('orang.update', $orang) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                @include('orang.partials.form', [
                    'orang' => $orang,
                    'pokjaList' => $pokjaList,
                    'rtList' => $rtList,
                ])
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('orang.show', $orang) }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </form>
@endsection
