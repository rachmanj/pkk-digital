@extends('layouts.app', [
    'title' => 'Buku Inventaris — Buku PKK Digital',
    'header' => 'Buku Inventaris',
])

@section('page_content')
    @php
        use App\Models\InventarisBarang;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body py-2 small">
                    <div class="text-muted">Jenis barang</div>
                    <div class="fs-5 fw-semibold">{{ number_format($ringkasan['jenis_barang'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body py-2 small">
                    <div class="text-muted">Total unit</div>
                    <div class="fs-5 fw-semibold">{{ number_format($ringkasan['total_unit'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body py-2 small d-flex flex-wrap gap-3">
                    <span><strong>Baik:</strong> {{ number_format($ringkasan['baik'], 0, ',', '.') }}</span>
                    <span><strong>Rusak ringan:</strong> {{ number_format($ringkasan['rusak_ringan'], 0, ',', '.') }}</span>
                    <span><strong>Rusak berat:</strong> {{ number_format($ringkasan['rusak_berat'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('inventaris.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="buku">Buku</label>
                    <select name="buku" id="buku" class="form-select form-select-sm">
                        <option value="kelurahan" @selected($filters['buku'] === 'kelurahan')>Kelurahan</option>
                        @foreach ($pokjaList as $pokja)
                            <option value="pokja-{{ $pokja->id }}" @selected($filters['buku'] === 'pokja-'.$pokja->id)>
                                Pokja {{ $pokja->kode }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="tahun">Tahun</label>
                    <input type="number" name="tahun" id="tahun" class="form-control form-control-sm"
                        min="2000" max="2100" value="{{ $filters['tahun'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="kondisi">Kondisi</label>
                    <select name="kondisi" id="kondisi" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="{{ InventarisBarang::KONDISI_BAIK }}" @selected($filters['kondisi'] === InventarisBarang::KONDISI_BAIK)>Baik</option>
                        <option value="{{ InventarisBarang::KONDISI_RUSAK_RINGAN }}" @selected($filters['kondisi'] === InventarisBarang::KONDISI_RUSAK_RINGAN)>Rusak ringan</option>
                        <option value="{{ InventarisBarang::KONDISI_RUSAK_BERAT }}" @selected($filters['kondisi'] === InventarisBarang::KONDISI_RUSAK_BERAT)>Rusak berat</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="q">Cari nama / asal / tempat penyimpanan</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm" value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('inventaris.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        @can(\App\Support\PkkPermission::KELOLA_INVENTARIS)
            <a href="{{ route('inventaris.create', ['buku' => $filters['buku']]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah Barang
            </a>
        @endcan
        @include('partials.cetak-toolbar', ['kodeBuku' => 'buku_inventaris'])
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NAMA BARANG</th>
                        <th>ASAL</th>
                        <th>TANGGAL TERIMA</th>
                        <th class="text-end">JUMLAH</th>
                        <th>TEMPAT</th>
                        <th>KONDISI</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barang as $item)
                        <tr>
                            <td>{{ $item->nama_barang }}</td>
                            <td>{{ $item->asal_barang ?: '—' }}</td>
                            <td>{{ $item->tanggal_terima?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-end">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                            <td>{{ $item->tempat_penyimpanan ?: '—' }}</td>
                            <td>{{ $item->labelKondisi() }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('inventaris.show', $item) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada data inventaris.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($barang->hasPages())
            <div class="card-footer">{{ $barang->links() }}</div>
        @endif
    </div>
@endsection
