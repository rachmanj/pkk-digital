@extends('layouts.app', [
    'title' => 'Agenda Surat — Buku PKK Digital',
    'header' => 'Agenda Surat',
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
            <form method="get" action="{{ route('agenda-surat.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="jenis" value="{{ $jenis }}">
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
                <div class="col-md-3">
                    <label class="form-label" for="q">Cari no. surat / perihal / {{ $jenis === 'masuk' ? 'dari' : 'kepada' }}</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm"
                        value="{{ $filters['q'] }}">
                </div>
                @if ($jenis === 'masuk')
                    <div class="col-md-2">
                        <label class="form-label" for="status_tindak">Status tindak lanjut</label>
                        <select name="status_tindak" id="status_tindak" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="belum" @selected($filters['status_tindak'] === 'belum')>Belum disposisi</option>
                            <option value="baru" @selected($filters['status_tindak'] === 'baru')>Baru</option>
                            <option value="proses" @selected($filters['status_tindak'] === 'proses')>Proses</option>
                            <option value="selesai" @selected($filters['status_tindak'] === 'selesai')>Selesai</option>
                        </select>
                    </div>
                @endif
                <div class="col-md-{{ $jenis === 'masuk' ? '3' : '5' }} d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                    <a href="{{ route('agenda-surat.index', ['jenis' => $jenis]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link @if ($jenis === 'masuk') active @endif"
                href="{{ route('agenda-surat.index', array_merge(request()->except('page'), ['jenis' => 'masuk'])) }}">
                Surat Masuk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if ($jenis === 'keluar') active @endif"
                href="{{ route('agenda-surat.index', array_merge(request()->except('page'), ['jenis' => 'keluar'])) }}">
                Surat Keluar
            </a>
        </li>
    </ul>

    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <a href="{{ route('agenda-surat.create', ['jenis' => $jenis, 'buku' => $filters['buku']]) }}"
            class="btn btn-success btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Surat {{ $jenis === 'masuk' ? 'Masuk' : 'Keluar' }}
        </a>
        @include('partials.cetak-toolbar', [
            'kodeBuku' => $jenis === 'masuk' ? 'agenda_surat_masuk' : 'agenda_surat_keluar',
        ])
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if ($jenis === 'masuk')
                <table class="table table-sm table-striped table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>NO</th>
                            <th>TANGGAL SURAT</th>
                            <th>TANGGAL TERIMA</th>
                            <th>NO. SURAT YANG DITERIMA</th>
                            <th>DARI</th>
                            <th>PERIHAL</th>
                            <th>LAMPIRAN</th>
                            <th>DITERUSKAN KEPADA</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agendaSurat as $surat)
                            <tr>
                                <td>{{ $surat->no_urut_tahun }}</td>
                                <td>{{ $surat->tanggal_surat?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $surat->tanggal_terima?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $surat->no_surat }}</td>
                                <td>{{ $surat->dari ?: '—' }}</td>
                                <td>{{ $surat->perihal }}</td>
                                <td>{{ $surat->lampiran ?: ($surat->file_path ? '1 berkas' : '—') }}</td>
                                <td>{{ $surat->diteruskanKepadaRingkas() }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('agenda-surat.show', $surat) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada surat masuk.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="table table-sm table-striped table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>NO</th>
                            <th>NO. SURAT</th>
                            <th>TANGGAL SURAT</th>
                            <th>KEPADA</th>
                            <th>PERIHAL</th>
                            <th>LAMPIRAN</th>
                            <th>TEMBUSAN</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agendaSurat as $surat)
                            <tr>
                                <td>{{ $surat->no_urut_tahun }}</td>
                                <td>{{ $surat->no_surat }}</td>
                                <td>{{ $surat->tanggal_surat?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $surat->kepada ?: '—' }}</td>
                                <td>{{ $surat->perihal }}</td>
                                <td>{{ $surat->lampiran ?: ($surat->file_path ? '1 berkas' : '—') }}</td>
                                <td>{{ $surat->tembusan ?: '—' }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('agenda-surat.show', $surat) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada surat keluar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
        @if ($agendaSurat->hasPages())
            <div class="card-footer py-2">
                {{ $agendaSurat->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection
