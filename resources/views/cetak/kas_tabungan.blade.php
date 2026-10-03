@extends('cetak.layout')

@section('isi_cetak')
    @php
        use App\Support\FormatUang;
        $lebarKolom = $tampilan['lebar_kolom'] ?? [];
        $totalPenerimaan = (float) ($dataset['total_penerimaan'] ?? 0);
    @endphp
    <table class="buku {{ $tampilan['kelas_orientasi'] ?? '' }}">
        <colgroup>
            @foreach ($dataset['kolom'] as $kolom)
                <col style="width: {{ $lebarKolom[$kolom['key']] ?? 'auto' }}">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                @foreach ($dataset['kolom'] as $kolom)
                    <th scope="col">{{ $kolom['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($dataset['baris'] as $baris)
                <tr>
                    @foreach ($dataset['kolom'] as $kolom)
                        <td>{{ $baris['cells'][$kolom['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($dataset['kolom']) }}" style="text-align: center;">Belum ada data.</td>
                </tr>
            @endforelse
            <tr>
                @foreach ($dataset['kolom'] as $kolom)
                    @if ($kolom['key'] === 'uraian')
                        <td class="fw-bold">JUMLAH</td>
                    @elseif ($kolom['key'] === 'jumlah_penerimaan')
                        <td class="fw-bold">{{ number_format($totalPenerimaan, 0, ',', '.') }}</td>
                    @else
                        <td></td>
                    @endif
                @endforeach
            </tr>
        </tbody>
    </table>
@endsection
