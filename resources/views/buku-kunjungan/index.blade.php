@extends('layouts.app', [
    'title' => 'Buku Kunjungan — Buku PKK Digital',
    'header' => 'Buku Kunjungan',
])

@section('page_content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('buku-kunjungan.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="tahun">Tahun</label>
                    <input type="number" name="tahun" id="tahun" class="form-control form-control-sm"
                        min="2000" max="2100" value="{{ $filters['tahun'] }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="q">Cari nama / lokasi kunjungan</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm"
                        value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-4 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('buku-kunjungan.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('buku-kunjungan.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Kunjungan
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NO</th>
                        <th>TANGGAL</th>
                        <th>NAMA</th>
                        <th>JABATAN</th>
                        <th>LOKASI KUNJUNGAN</th>
                        <th>JENIS KEGIATAN</th>
                        <th>TANDA TANGAN</th>
                        <th>KETERANGAN</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bukuKunjungan as $baris)
                        <tr>
                            <td>{{ $baris->no_urut_tahun }}</td>
                            <td>{{ $baris->tanggal?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $baris->nama }}</td>
                            <td>{{ $baris->jabatan ?: '—' }}</td>
                            <td>{{ $baris->lokasi_kunjungan ?: '—' }}</td>
                            <td>{{ $baris->jenis_kegiatan ?: '—' }}</td>
                            <td>—</td>
                            <td>{{ Str::limit($baris->keterangan ?: '—', 40) }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('buku-kunjungan.show', $baris) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Belum ada kunjungan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bukuKunjungan->hasPages())
            <div class="card-footer py-2">
                {{ $bukuKunjungan->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection
