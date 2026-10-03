@php
    $tampilan = $tampilan ?? app(\App\Services\BukuCetakTampilan::class)->untukKode($dataset['kode']);
    $orientasi = $tampilan['orientasi'];
    $kelasOrientasi = $tampilan['kelas_orientasi'];
    $lebarMm = \App\Services\BukuCetakTampilan::KERTAS_LEBAR_MM;
    $tinggiMm = \App\Services\BukuCetakTampilan::KERTAS_TINGGI_MM;
    $marginV = \App\Services\BukuCetakTampilan::MARGIN_ATAS_BAWAH_MM;
    $marginH = \App\Services\BukuCetakTampilan::MARGIN_KIRI_KANAN_MM;
    $ukuranKertas = $orientasi === 'landscape'
        ? "{$tinggiMm}mm {$lebarMm}mm"
        : "{$lebarMm}mm {$tinggiMm}mm";
@endphp
<!DOCTYPE html>
<html lang="id" class="{{ $kelasOrientasi }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $dataset['judul'] }} — Cetak</title>
    <style>
        @page {
            size: {{ $ukuranKertas }};
            margin: {{ $marginV }}mm {{ $marginH }}mm;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 9pt;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 8px;
        }
        body.cetak-landscape table.buku th {
            font-size: 6.5pt;
        }
        body.cetak-landscape table.buku td {
            font-size: 7.5pt;
        }
        .kop {
            text-align: center;
            margin-bottom: 8px;
        }
        .kop h1 {
            font-size: 10pt;
            font-weight: bold;
            margin: 0 0 3px;
            text-transform: uppercase;
        }
        .kop p {
            margin: 0;
            font-size: 8pt;
        }
        .judul-buku {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            margin: 6px 0 3px;
            text-decoration: underline;
        }
        .tahun {
            text-align: center;
            margin-bottom: 8px;
            font-size: 9pt;
        }
        table.buku {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.buku th,
        table.buku td {
            border: 1px solid #333;
            padding: 2px 3px;
            vertical-align: top;
            word-break: normal;
            overflow-wrap: normal;
            hyphens: none;
        }
        table.buku th {
            font-size: 7pt;
            text-align: center;
            font-weight: bold;
        }
        table.buku td {
            font-size: 8pt;
            overflow-wrap: break-word;
        }
        td.kolom-ttd {
            min-height: 24px;
            height: 24px;
        }
        .blok-ttd {
            margin-top: 20px;
            display: flex;
            justify-content: space-around;
            gap: 16px;
        }
        .blok-ttd .kolom {
            text-align: center;
            width: 45%;
            font-size: 9pt;
        }
        .blok-ttd .garis {
            margin: 40px auto 4px;
            border-bottom: 1px solid #000;
            width: 70%;
            min-height: 1px;
        }
        .no-print {
            margin-bottom: 12px;
        }
        .no-print a,
        .no-print button {
            font-family: system-ui, sans-serif;
            font-size: 13px;
            margin-right: 8px;
        }
        .form-notulen dt {
            font-weight: bold;
            margin-top: 6px;
        }
        .form-notulen dd {
            margin: 2px 0 8px 0;
            white-space: pre-wrap;
        }
    </style>
</head>
<body class="{{ $kelasOrientasi }}">
    @if (($mode ?? 'screen') === 'screen')
        <div class="no-print">
            <button type="button" onclick="window.print()">Cetak</button>
            <a href="{{ request()->fullUrlWithQuery([]) }}">Muat ulang</a>
            <a href="{{ route('cetak.pdf', array_merge(['buku' => $dataset['kode']], request()->query())) }}">Unduh PDF</a>
            <a href="{{ route('export.buku', array_merge(['buku' => $dataset['kode']], request()->query())) }}">Export Excel</a>
        </div>
    @endif

    <div class="kop">
        <h1>Tim Penggerak Pemberdayaan Kesejahteraan Keluarga</h1>
        <p>Kelurahan Gunung Sari Ilir, Kecamatan Balikpapan Tengah</p>
    </div>

    <div class="judul-buku">{{ $dataset['judul'] }}</div>
    <div class="tahun">Tahun {{ $dataset['tahun'] }}</div>

    @yield('isi_cetak')

    @if (!empty($dataset['tanda_tangan']))
        <div class="blok-ttd">
            @foreach ($dataset['tanda_tangan'] as $ttd)
                <div class="kolom">
                    <div>{{ $ttd['peran'] }}</div>
                    <div class="garis"></div>
                    <div>(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>
                </div>
            @endforeach
        </div>
    @endif
</body>
</html>
