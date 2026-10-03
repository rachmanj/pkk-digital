@extends('cetak.layout')

@section('isi_cetak')
    @php
        use App\Support\FormatTanggalIndonesia;
        use App\Support\FormatUang;
        $record = $dataset['tutup_buku']['record'];
        $tanggal = $record->tanggal_tutup;
    @endphp

    <p style="text-align: justify; font-size: 10pt; margin: 12px 0;">
        Pada hari ini {{ FormatTanggalIndonesia::hariIniLengkap($tanggal) }}
        Buku Kas Umum ditutup dengan keadaan sebagai berikut:
    </p>

    <p style="font-weight: bold; margin: 16px 0 8px;">Sisa Buku Kas Umum</p>
    <table class="buku" style="max-width: 480px;">
        <tbody>
            <tr>
                <td style="width: 70%;">a. Sisa Bank</td>
                <td style="text-align: right;">{{ FormatUang::rupiah($record->sisa_bank) }}</td>
            </tr>
            <tr>
                <td>b. Sisa Kas/Tunai</td>
                <td style="text-align: right;">{{ FormatUang::rupiah($record->sisa_tunai) }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">TOTAL</td>
                <td style="text-align: right; font-weight: bold;">{{ FormatUang::rupiah($record->total) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($record->catatan)
        <p style="margin-top: 12px; font-size: 9pt;"><strong>Catatan:</strong> {{ $record->catatan }}</p>
    @endif

    <div class="blok-ttd" style="margin-top: 32px;">
        <div class="kolom">
            <div>Mengetahui,</div>
            <div>Ketua Umum/Ketua</div>
            <div class="garis"></div>
            <div>
                @if ($record->nama_ketua)
                    <strong>{{ $record->nama_ketua }}</strong>
                @else
                    (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
                @endif
            </div>
        </div>
        <div class="kolom">
            <div>Bendahara</div>
            <div class="garis"></div>
            <div>
                @if ($record->nama_bendahara)
                    <strong>{{ $record->nama_bendahara }}</strong>
                @else
                    (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
                @endif
            </div>
        </div>
    </div>
@endsection
