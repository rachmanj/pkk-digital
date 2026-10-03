@extends('layouts.app', [
    'title' => 'Detail Pengurus — Buku PKK Digital',
    'header' => 'Detail Pengurus',
])

@section('page_content')
    @php
        use App\Models\StrukturPengurus;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Unit</dt>
                <dd class="col-sm-9">{{ StrukturPengurus::labelUnit($struktur->unit) }}</dd>
                @if ($struktur->pokja)
                    <dt class="col-sm-3">Pokja</dt>
                    <dd class="col-sm-9">Pokja {{ $struktur->pokja->kode }} — {{ $struktur->pokja->nama }}</dd>
                @endif
                @if ($struktur->rt)
                    <dt class="col-sm-3">RT</dt>
                    <dd class="col-sm-9">{{ $struktur->rt }}</dd>
                @endif
                <dt class="col-sm-3">Jabatan</dt>
                <dd class="col-sm-9">{{ $struktur->jabatan }}</dd>
                <dt class="col-sm-3">Nama</dt>
                <dd class="col-sm-9">{{ $struktur->nama }}</dd>
                <dt class="col-sm-3">Urutan</dt>
                <dd class="col-sm-9">{{ $struktur->urutan }}</dd>
                <dt class="col-sm-3">Keterangan</dt>
                <dd class="col-sm-9">{{ $struktur->keterangan ?: '—' }}</dd>
            </dl>
        </div>
        <div class="card-footer d-flex flex-wrap gap-2">
            <a href="{{ route('struktur.index', ['unit' => $struktur->unit]) }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            @if ($canKelola)
                <a href="{{ route('struktur.edit', $struktur) }}" class="btn btn-primary btn-sm">Ubah</a>
                <form method="post" action="{{ route('struktur.destroy', $struktur) }}" onsubmit="return confirm('Hapus pengurus ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                </form>
            @endif
        </div>
    </div>
@endsection
