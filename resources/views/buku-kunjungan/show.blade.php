@extends('layouts.app', [
    'title' => 'Detail Buku Kunjungan — Buku PKK Digital',
    'header' => 'Detail Buku Kunjungan',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('buku-kunjungan.index', ['tahun' => $bukuKunjungan->tahun]) }}"
            class="btn btn-outline-secondary btn-sm">
            Kembali ke daftar
        </a>
        <a href="{{ route('buku-kunjungan.edit', $bukuKunjungan) }}" class="btn btn-primary btn-sm">Ubah</a>
        <form method="post" action="{{ route('buku-kunjungan.destroy', $bukuKunjungan) }}"
            onsubmit="return confirm('Hapus entri buku kunjungan ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            No. {{ $bukuKunjungan->no_urut_tahun }} / {{ $bukuKunjungan->tahun }} — Kelurahan
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Tanggal</dt>
                <dd class="col-sm-9">{{ $bukuKunjungan->tanggal?->format('d/m/Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Nama</dt>
                <dd class="col-sm-9">{{ $bukuKunjungan->nama }}</dd>
                <dt class="col-sm-3">Jabatan</dt>
                <dd class="col-sm-9">{{ $bukuKunjungan->jabatan ?: '—' }}</dd>
                <dt class="col-sm-3">Lokasi kunjungan</dt>
                <dd class="col-sm-9">{{ $bukuKunjungan->lokasi_kunjungan ?: '—' }}</dd>
                <dt class="col-sm-3">Jenis kegiatan</dt>
                <dd class="col-sm-9">{{ $bukuKunjungan->jenis_kegiatan ?: '—' }}</dd>
                <dt class="col-sm-3">Tanda tangan</dt>
                <dd class="col-sm-9">—</dd>
                <dt class="col-sm-3">Keterangan</dt>
                <dd class="col-sm-9">{{ $bukuKunjungan->keterangan ?: '—' }}</dd>
            </dl>
        </div>
    </div>
@endsection
