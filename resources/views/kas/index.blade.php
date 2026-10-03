@extends('layouts.app', [
    'title' => 'Kas dan Tabungan — Buku PKK Digital',
    'header' => 'Kas dan Tabungan',
])

@section('page_content')
    @php
        use App\Models\KasTransaksi;
        use App\Support\FormatUang;
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($ringkasan)
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card border-success h-100">
                    <div class="card-header py-2 bg-success text-white">Tunai</div>
                    <div class="card-body small">
                        <div>Saldo awal: {{ FormatUang::rupiah($ringkasan['tunai']['saldo_awal']) }}</div>
                        <div>Masuk: {{ FormatUang::rupiah($ringkasan['tunai']['masuk']) }}</div>
                        <div>Keluar: {{ FormatUang::rupiah($ringkasan['tunai']['keluar']) }}</div>
                        <div class="fw-semibold mt-1">Saldo akhir: {{ FormatUang::rupiah($ringkasan['tunai']['saldo_akhir']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-primary h-100">
                    <div class="card-header py-2 bg-primary text-white">Bank</div>
                    <div class="card-body small">
                        <div>Saldo awal: {{ FormatUang::rupiah($ringkasan['bank']['saldo_awal']) }}</div>
                        <div>Masuk: {{ FormatUang::rupiah($ringkasan['bank']['masuk']) }}</div>
                        <div>Keluar: {{ FormatUang::rupiah($ringkasan['bank']['keluar']) }}</div>
                        <div class="fw-semibold mt-1">Saldo akhir: {{ FormatUang::rupiah($ringkasan['bank']['saldo_akhir']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-dark h-100">
                    <div class="card-header py-2">Total</div>
                    <div class="card-body small">
                        <div>Saldo awal: {{ FormatUang::rupiah($ringkasan['total']['saldo_awal']) }}</div>
                        <div>Masuk: {{ FormatUang::rupiah($ringkasan['total']['masuk']) }}</div>
                        <div>Keluar: {{ FormatUang::rupiah($ringkasan['total']['keluar']) }}</div>
                        <div class="fw-semibold mt-1">Saldo akhir: {{ FormatUang::rupiah($ringkasan['total']['saldo_akhir']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('kas.index') }}" class="row g-2 align-items-end">
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
                    <label class="form-label" for="jenis">Jenis</label>
                    <select name="jenis" id="jenis" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="masuk" @selected($filters['jenis'] === 'masuk')>Pemasukan</option>
                        <option value="keluar" @selected($filters['jenis'] === 'keluar')>Pengeluaran</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="bulan">Bulan</label>
                    <select name="bulan" id="bulan" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (range(1, 12) as $b)
                            <option value="{{ $b }}" @selected($filters['bulan'] === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="q">Cari uraian / no. bukti</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm" value="{{ $filters['q'] }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @can(\App\Support\PkkPermission::KELOLA_KAS)
        <div class="card mb-3">
            <div class="card-header py-2">Saldo awal tahun {{ $filters['tahun'] }}</div>
            <div class="card-body">
                <form method="post" action="{{ route('kas.saldo-awal.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="buku" value="{{ $filters['buku'] }}">
                    <input type="hidden" name="tahun" value="{{ $filters['tahun'] }}">
                    <div class="col-md-3">
                        <label class="form-label" for="saldo_tunai">Saldo awal tunai (Rp)</label>
                        <input type="number" name="saldo_tunai" id="saldo_tunai" step="0.01" min="0"
                            class="form-control form-control-sm" value="{{ old('saldo_tunai', $saldoAwalForm['saldo_tunai']) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="saldo_bank">Saldo awal bank (Rp)</label>
                        <input type="number" name="saldo_bank" id="saldo_bank" step="0.01" min="0"
                            class="form-control form-control-sm" value="{{ old('saldo_bank', $saldoAwalForm['saldo_bank']) }}" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-primary btn-sm">Simpan saldo awal</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <div class="d-flex flex-wrap gap-2 mb-3">
        @can(\App\Support\PkkPermission::KELOLA_KAS)
            <a href="{{ route('kas.create', ['buku' => $filters['buku']]) }}" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg"></i> Tambah transaksi
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NO</th>
                        <th>TANGGAL</th>
                        @if ($filters['buku'] === 'kelurahan')
                            <th>SUMBER DANA</th>
                        @endif
                        <th>URAIAN PEMASUKAN</th>
                        <th>URAIAN PENGELUARAN</th>
                        <th>TEMPAT</th>
                        @if ($filters['buku'] === 'kelurahan')
                            <th>NO. BUKTI</th>
                        @endif
                        <th>JUMLAH</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transaksi as $index => $baris)
                        <tr>
                            <td>{{ $transaksi->firstItem() + $index }}</td>
                            <td>{{ $baris->tanggal?->format('d/m/Y') ?? '—' }}</td>
                            @if ($filters['buku'] === 'kelurahan')
                                <td>{{ $baris->sumber_dana ?: '—' }}</td>
                            @endif
                            <td>
                                @if ($baris->jenis === KasTransaksi::JENIS_MASUK)
                                    {{ $baris->uraian }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($baris->jenis === KasTransaksi::JENIS_KELUAR)
                                    {{ $baris->uraian }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $baris->pos === KasTransaksi::POS_BANK ? 'Bank' : 'Tunai' }}</td>
                            @if ($filters['buku'] === 'kelurahan')
                                <td>{{ $baris->no_bukti ?: '—' }}</td>
                            @endif
                            <td class="text-nowrap">{{ FormatUang::rupiah($baris->jumlah) }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('kas.show', $baris) }}" class="btn btn-outline-primary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $filters['buku'] === 'kelurahan' ? 9 : 7 }}" class="text-center text-muted py-4">
                                Belum ada transaksi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transaksi->hasPages())
            <div class="card-footer py-2">
                {{ $transaksi->onEachSide(1)->links('pagination::bootstrap-4') }}
            </div>
        @endif
    </div>
@endsection
