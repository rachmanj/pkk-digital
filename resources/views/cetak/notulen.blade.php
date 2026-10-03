@extends('cetak.layout')

@section('isi_cetak')
    @php
        $kegiatan = $dataset['kegiatan'];
        $notulen = $dataset['notulen'];
    @endphp

    @if ($kegiatan === null)
        <p>Data kegiatan tidak ditemukan. Pilih kegiatan melalui parameter <code>kegiatan</code>.</p>
    @else
        <dl class="form-notulen">
            <dt>Nama kegiatan</dt>
            <dd>{{ $kegiatan->nama }}</dd>
            <dt>Tanggal</dt>
            <dd>{{ $kegiatan->tanggal?->format('d/m/Y') ?? '—' }}</dd>
            <dt>Tempat</dt>
            <dd>{{ $kegiatan->tempat ?: '—' }}</dd>
            <dt>Jenis kegiatan</dt>
            <dd>{{ $kegiatan->labelJenis() }}</dd>
            <dt>Macam rapat</dt>
            <dd>{{ $notulen?->macam_rapat ?: '—' }}</dd>
            <dt>Jumlah diundang</dt>
            <dd>{{ $notulen?->jumlah_diundang ?? '—' }}</dd>
            <dt>Jumlah hadir</dt>
            <dd>{{ $notulen?->jumlah_hadir ?? '—' }}</dd>
            <dt>Jumlah tidak hadir</dt>
            <dd>{{ $notulen?->jumlah_tidak_hadir ?? '—' }}</dd>
            <dt>Uraian jalannya rapat</dt>
            <dd>{{ $notulen?->uraian_jalannya ?: '—' }}</dd>
            <dt>Keputusan</dt>
            <dd>{{ $notulen?->keputusan ?: '—' }}</dd>
            <dt>Lain-lain</dt>
            <dd>{{ $notulen?->lain_lain ?: '—' }}</dd>
            <dt>Penutup</dt>
            <dd>{{ $notulen?->penutup ?: '—' }}</dd>
            <dt>Tempat dan tanggal tanda tangan</dt>
            <dd>{{ $notulen?->tempat_tanggal_ttd ?: '—' }}</dd>
        </dl>
    @endif
@endsection
