@extends('layouts.app', [
    'title' => 'Detail Transaksi Kas — Buku PKK Digital',
    'header' => 'Detail Transaksi Kas',
])

@section('page_content')
    @php
        use App\Models\KasTransaksi;
        use App\Support\FormatUang;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('kas.index', ['buku' => $transaksi->pokja_id ? 'pokja-'.$transaksi->pokja_id : 'kelurahan', 'tahun' => $transaksi->tahun]) }}"
            class="btn btn-outline-secondary btn-sm">
            Kembali ke daftar
        </a>
        @can(\App\Support\PkkPermission::KELOLA_KAS)
            <a href="{{ route('kas.edit', $transaksi) }}" class="btn btn-primary btn-sm">Ubah</a>
            <form method="post" action="{{ route('kas.destroy', $transaksi) }}"
                onsubmit="return confirm('Hapus transaksi kas ini?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
            </form>
        @endcan
    </div>

    <div class="card">
        <div class="card-header">
            Transaksi {{ $transaksi->tahun }}
            @if ($transaksi->pokja)
                — Pokja {{ $transaksi->pokja->kode }}
            @else
                — Kelurahan (Kas Umum)
            @endif
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Tanggal</dt>
                <dd class="col-sm-9">{{ $transaksi->tanggal?->format('d/m/Y') ?? '—' }}</dd>
                <dt class="col-sm-3">Jenis</dt>
                <dd class="col-sm-9">{{ $transaksi->jenis === KasTransaksi::JENIS_MASUK ? 'Pemasukan' : 'Pengeluaran' }}</dd>
                <dt class="col-sm-3">Tempat uang</dt>
                <dd class="col-sm-9">{{ $transaksi->pos === KasTransaksi::POS_BANK ? 'Bank' : 'Tunai' }}</dd>
                <dt class="col-sm-3">Jumlah</dt>
                <dd class="col-sm-9">{{ FormatUang::rupiah($transaksi->jumlah) }}</dd>
                <dt class="col-sm-3">Sumber dana</dt>
                <dd class="col-sm-9">{{ $transaksi->sumber_dana ?: '—' }}</dd>
                <dt class="col-sm-3">Nomor bukti kas</dt>
                <dd class="col-sm-9">{{ $transaksi->no_bukti ?: '—' }}</dd>
                <dt class="col-sm-3">Uraian</dt>
                <dd class="col-sm-9">{{ $transaksi->uraian }}</dd>
                <dt class="col-sm-3">Dicatat oleh</dt>
                <dd class="col-sm-9">{{ $transaksi->pembuat?->name ?? '—' }}</dd>
            </dl>
        </div>
    </div>
@endsection
