@extends('layouts.app', [
    'title' => 'Detail Inventaris — Buku PKK Digital',
    'header' => 'Detail Inventaris',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('inventaris.index', ['buku' => $inventarisBarang->pokja_id ? 'pokja-'.$inventarisBarang->pokja_id : 'kelurahan', 'tahun' => $inventarisBarang->tahun]) }}"
            class="btn btn-outline-secondary btn-sm">
            Kembali ke daftar
        </a>
        @can(\App\Support\PkkPermission::KELOLA_INVENTARIS)
            <a href="{{ route('inventaris.edit', $inventarisBarang) }}" class="btn btn-primary btn-sm">Ubah</a>
            <form method="post" action="{{ route('inventaris.destroy', $inventarisBarang) }}"
                onsubmit="return confirm('Hapus barang inventaris ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
        @endcan
    </div>

    <div class="card">
        <div class="card-header">
            {{ $inventarisBarang->nama_barang }} — {{ $inventarisBarang->tahun }}
            @if ($inventarisBarang->pokja)
                — Pokja {{ $inventarisBarang->pokja->kode }}
            @else
                — Kelurahan
            @endif
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Nama barang</dt>
                <dd class="col-sm-9">{{ $inventarisBarang->nama_barang }}</dd>
                <dt class="col-sm-3">Asal barang</dt>
                <dd class="col-sm-9">{{ $inventarisBarang->asal_barang ?: '—' }}</dd>
                <dt class="col-sm-3">Tanggal penerimaan/pembelian</dt>
                <dd class="col-sm-9">{{ $inventarisBarang->tanggal_terima?->format('d/m/Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Jumlah</dt>
                <dd class="col-sm-9">{{ number_format($inventarisBarang->jumlah, 0, ',', '.') }}</dd>
                <dt class="col-sm-3">Tempat penyimpanan</dt>
                <dd class="col-sm-9">{{ $inventarisBarang->tempat_penyimpanan ?: '—' }}</dd>
                <dt class="col-sm-3">Kondisi</dt>
                <dd class="col-sm-9">{{ $inventarisBarang->labelKondisi() }}</dd>
                <dt class="col-sm-3">Keterangan</dt>
                <dd class="col-sm-9">{{ $inventarisBarang->keterangan ?: '—' }}</dd>
                @if ($inventarisBarang->pencatat)
                    <dt class="col-sm-3">Dicatat oleh</dt>
                    <dd class="col-sm-9">{{ $inventarisBarang->pencatat->name }}</dd>
                @endif
            </dl>
        </div>
    </div>
@endsection
