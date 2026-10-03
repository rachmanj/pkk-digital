@extends('cetak.layout')

@section('isi_cetak')
    <table class="buku {{ $tampilan['kelas_orientasi'] ?? '' }} program-kerja-matriks">
        <thead>
            <tr>
                <th rowspan="2" scope="col">NO.</th>
                <th rowspan="2" scope="col">JENIS KEGIATAN</th>
                <th colspan="12" scope="colgroup">BULAN PERENCANAAN</th>
                <th colspan="12" scope="colgroup">BULAN PELAKSANAAN</th>
            </tr>
            <tr>
                @for ($i = 1; $i <= 12; $i++)
                    <th scope="col">{{ $i }}</th>
                @endfor
                @for ($i = 1; $i <= 12; $i++)
                    <th scope="col">{{ $i }}</th>
                @endfor
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
                    <td colspan="26" style="text-align: center;">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
