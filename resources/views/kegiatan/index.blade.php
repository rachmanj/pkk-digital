@extends('layouts.app', [
    'title' => 'Kegiatan — Buku PKK Digital',
    'header' => 'Kegiatan',
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
            <form method="get" action="{{ route('kegiatan.index') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="tahun">Tahun</label>
                    <input type="number" name="tahun" id="tahun" class="form-control form-control-sm"
                        min="2000" max="2100" value="{{ $filters['tahun'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="bulan">Bulan</label>
                    <select name="bulan" id="bulan" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @for ($b = 1; $b <= 12; $b++)
                            <option value="{{ $b }}" @selected($filters['bulan'] === $b)>
                                {{ \Carbon\Carbon::create(null, $b, 1)->locale('id')->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="jenis">Jenis</label>
                    <select name="jenis" id="jenis" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($jenisList as $jenisItem)
                            <option value="{{ $jenisItem }}" @selected($filters['jenis'] === $jenisItem)>
                                {{ (new \App\Models\Kegiatan(['jenis' => $jenisItem]))->labelJenis() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="pokja_id">Pokja</label>
                    <select name="pokja_id" id="pokja_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($pokjaList as $pokja)
                            <option value="{{ $pokja->id }}" @selected($filters['pokja_id'] === $pokja->id)>
                                Pokja {{ $pokja->kode }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="q">Cari nama / tempat</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm"
                        value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('kegiatan.create') }}" class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Kegiatan
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Nama</th>
                        <th>Jenis</th>
                        <th>Tempat</th>
                        <th>Pokja</th>
                        <th class="text-center">Peserta</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kegiatan as $item)
                        <tr>
                            <td>{{ $item->tanggal?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $item->nama }}</td>
                            <td>{{ $item->labelJenis() }}</td>
                            <td>{{ $item->tempat ?: '—' }}</td>
                            <td>
                                @if ($item->pokja)
                                    Pokja {{ $item->pokja->kode }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-secondary">{{ $item->presensi_count }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('kegiatan.show', $item) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada kegiatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($kegiatan->hasPages())
            <div class="card-footer">
                {{ $kegiatan->links() }}
            </div>
        @endif
    </div>
@endsection
