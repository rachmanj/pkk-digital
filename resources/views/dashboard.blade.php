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
