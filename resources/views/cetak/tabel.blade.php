@extends('cetak.layout')

@section('isi_cetak')
    @php
        $lebarKolom = $tampilan['lebar_kolom'] ?? [];
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
                        @php
                            $nilai = $baris['cells'][$kolom['key']] ?? '';
                            $isTtd = $kolom['key'] === 'tanda_tangan';
                        @endphp
                        <td @class(['kolom-ttd' => $isTtd])>{{ $isTtd ? '' : $nilai }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($dataset['kolom']) }}" style="text-align: center;">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
