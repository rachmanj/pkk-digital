@extends('layouts.app', [
    'title' => 'Detail Buku Tamu — Buku PKK Digital',
    'header' => 'Detail Buku Tamu',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('buku-tamu.index', ['buku' => $bukuTamu->pokja_id ? 'pokja-'.$bukuTamu->pokja_id : 'kelurahan', 'tahun' => $bukuTamu->tahun]) }}"
            class="btn btn-outline-secondary btn-sm">
            Kembali ke daftar
        </a>
        <a href="{{ route('buku-tamu.edit', $bukuTamu) }}" class="btn btn-primary btn-sm">Ubah</a>
        <form method="post" action="{{ route('buku-tamu.destroy', $bukuTamu) }}"
            onsubmit="return confirm('Hapus entri buku tamu ini?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            No. {{ $bukuTamu->no_urut_tahun }} / {{ $bukuTamu->tahun }}
            @if ($bukuTamu->pokja)
                — Pokja {{ $bukuTamu->pokja->kode }}
            @else
                — Kelurahan
            @endif
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Tanggal</dt>
                <dd class="col-sm-9">{{ $bukuTamu->tanggal?->format('d/m/Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Nama tamu</dt>
                <dd class="col-sm-9">{{ $bukuTamu->nama_tamu }}</dd>
                <dt class="col-sm-3">Alamat</dt>
                <dd class="col-sm-9">{{ $bukuTamu->alamat ?: '—' }}</dd>
                <dt class="col-sm-3">Keperluan</dt>
                <dd class="col-sm-9">{{ $bukuTamu->keperluan ?: '—' }}</dd>
                <dt class="col-sm-3">Tujuan</dt>
                <dd class="col-sm-9">{{ $bukuTamu->tujuan ?: '—' }}</dd>
                <dt class="col-sm-3">Tanda tangan</dt>
                <dd class="col-sm-9">—</dd>
                <dt class="col-sm-3">Keterangan</dt>
                <dd class="col-sm-9">{{ $bukuTamu->keterangan ?: '—' }}</dd>
            </dl>
        </div>
    </div>
@endsection
