@php
    use App\Support\FormatUang;
    $tampilan = app(\App\Services\BukuCetakTampilan::class)->untukKode('kas_tabungan');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Bulanan Kas {{ $filters['tahun'] }}</title>
    <style>
        body { font-family: "Times New Roman", Times, serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 4px 6px; }
        th { background: #f0f0f0; }
        .text-end { text-align: right; }
        h1 { font-size: 12pt; text-align: center; }
    </style>
</head>
<body>
    <h1>Rekap Bulanan Kas — {{ $judulBuku }}</h1>
    <p style="text-align: center;">Tahun {{ $filters['tahun'] }}</p>
    <table>
        <thead>
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
            <tr>
                <td><strong>Total</strong></td>
                <td class="text-end"><strong>{{ FormatUang::rupiah($total['masuk']) }}</strong></td>
                <td class="text-end"><strong>{{ FormatUang::rupiah($total['keluar']) }}</strong></td>
                <td class="text-end"><strong>{{ FormatUang::rupiah($total['saldo_akhir']) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
