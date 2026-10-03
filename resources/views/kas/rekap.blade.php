@extends('layouts.app', [
    'title' => 'Rekap Bulanan Kas — Buku PKK Digital',
    'header' => 'Rekap Bulanan Kas',
])

@section('page_content')
    @php
        use App\Support\FormatUang;
    @endphp

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="{{ route('kas.rekap') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
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
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                    <a href="{{ route('kas.rekap.pdf', request()->query()) }}" class="btn btn-outline-secondary btn-sm">Cetak PDF</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2">Rekap {{ $filters['tahun'] }} — {{ $judulBuku }}</div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Bulan</th>
                        <th class="text-end">Pemasukan</th>
                        <th class="text-end">Pengeluaran</th>
                        <th class="text-end">Saldo akhir berjalan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekap as $baris)
                        <tr>
                            <td>{{ $baris['nama_bulan'] }}</td>
                            <td class="text-end">{{ FormatUang::rupiah($baris['masuk']) }}</td>
                            <td class="text-end">{{ FormatUang::rupiah($baris['keluar']) }}</td>
                            <td class="text-end">{{ FormatUang::rupiah($baris['saldo_akhir']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="table-secondary fw-semibold">
                        <td>Total</td>
                        <td class="text-end">{{ FormatUang::rupiah($total['masuk']) }}</td>
                        <td class="text-end">{{ FormatUang::rupiah($total['keluar']) }}</td>
                        <td class="text-end">{{ FormatUang::rupiah($total['saldo_akhir']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('kas.index', ['buku' => $filters['buku'], 'tahun' => $filters['tahun']]) }}" class="btn btn-outline-primary btn-sm">
            Kembali ke daftar transaksi
        </a>
    </div>
@endsection
