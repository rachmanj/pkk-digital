@extends('layouts.app', [
    'title' => 'Dashboard — Buku PKK Digital',
    'header' => 'Dashboard',
])

@section('page_content')
    @if ($kelurahan === null)
        <div class="alert alert-warning" role="alert">
            Belum ada kelurahan aktif di database. Jalankan seeder master untuk mengisi data kelurahan dan pokja.
        </div>
    @else
        <div class="row">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="small-box text-bg-primary">
                    <div class="inner">
                        <h3>{{ $kelurahan->nama }}</h3>
                        <p>{{ $kelurahan->kecamatan }}, {{ $kelurahan->kota }}</p>
                    </div>
                    <div class="icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="small-box text-bg-info">
                    <div class="inner">
                        <h3>{{ $pokjaCount }}</h3>
                        <p>Jumlah Pokja</p>
                    </div>
                    <div class="icon">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>

        @if ($ringkasanLintas !== null && $ringkasanLintas->isNotEmpty())
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Ringkasan lintas kelurahan</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Kelurahan</th>
                                <th class="text-end">Orang</th>
                                <th class="text-end">Surat</th>
                                <th class="text-end">Kegiatan</th>
                                <th class="text-end">Saldo kas (tahun ini)</th>
                                <th class="text-end">Program kerja</th>
                                <th class="text-end">Inventaris</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ringkasanLintas as $baris)
                                <tr>
                                    <td>{{ $baris['kelurahan']->nama }}</td>
                                    <td class="text-end">{{ number_format($baris['jumlah_orang'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($baris['jumlah_surat'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($baris['jumlah_kegiatan'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($baris['saldo_kas_akhir'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($baris['jumlah_program_kerja'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($baris['jumlah_inventaris'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Pokja</h3>
            </div>
            <div class="card-body p-0">
                @if ($pokja->isEmpty())
                    <p class="p-3 mb-0 text-muted">Belum ada data pokja untuk kelurahan ini.</p>
                @else
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pokja as $item)
                                <tr>
                                    <td>{{ $item->kode }}</td>
                                    <td>{{ $item->nama }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif
@endsection
