@extends('layouts.app', [
    'title' => 'Laporan ke PKK Kota — Buku PKK Digital',
    'header' => 'Laporan ke PKK Kota',
])

@section('page_content')
    @php
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $queryCetak = array_filter([
            'tahun' => $filters['tahun'],
            'bulan' => $filters['bulan'],
        ], fn ($v) => $v !== null && $v !== '');
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('laporan-kota.index') }}" class="row g-2 align-items-end">
                @if (auth()->user()?->hasRole('superadmin'))
                    <div class="col-md-4">
                        <label class="form-label" for="kelurahan_info">Kelurahan aktif</label>
                        <div id="kelurahan_info" class="form-control form-control-sm bg-light">
                            {{ $kelurahan?->nama ?? '—' }}
                            <span class="text-muted small">(gunakan pemilih kelurahan di menu atas)</span>
                        </div>
                    </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label" for="tahun">Tahun</label>
                    <input type="number" name="tahun" id="tahun" class="form-control form-control-sm"
                        min="2000" max="2100" value="{{ $filters['tahun'] }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="bulan">Bulan</label>
                    <select name="bulan" id="bulan" class="form-select form-select-sm">
                        <option value="">Seluruh tahun</option>
                        @foreach ($namaBulan as $no => $label)
                            <option value="{{ $no }}" @selected((int) ($filters['bulan'] ?? 0) === $no)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                    <a href="{{ route('cetak.show', ['buku' => 'laporan_kota'] + $queryCetak) }}" class="btn btn-outline-secondary btn-sm" target="_blank">Cetak / PDF</a>
                    <a href="{{ route('export.buku', ['buku' => 'laporan_kota'] + $queryCetak) }}" class="btn btn-outline-success btn-sm">Export Excel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="font-family: 'Times New Roman', Times, serif;">
            <div class="text-center mb-3">
                <h2 class="h6 fw-bold text-uppercase mb-1">Tim Penggerak Pemberdayaan Kesejahteraan Keluarga</h2>
                <p class="small mb-1">
                    Kelurahan {{ $kelurahan?->nama ?? '—' }}, Kecamatan {{ $kelurahan?->kecamatan ?? '—' }}
                    @if ($kelurahan?->kota)
                        , {{ $kelurahan->kota }}
                    @endif
                </p>
                <p class="fw-bold text-decoration-underline mb-0">{{ $dataset['judul'] }}</p>
                <p class="small mb-0">{{ $dataset['label_periode'] }}</p>
            </div>

            @include('laporan-kota.konten', ['dataset' => $dataset])

            <div class="row mt-4 text-center small">
                <div class="col-md-6">
                    <p class="mb-0">Mengetahui,<br>Ketua TP PKK</p>
                    <div style="height:64px;"></div>
                    <p class="mb-0">(.................................)</p>
                </div>
                <div class="col-md-6">
                    <p class="mb-0">Sekretaris</p>
                    <div style="height:64px;"></div>
                    <p class="mb-0">(.................................)</p>
                </div>
            </div>
        </div>
    </div>
@endsection
