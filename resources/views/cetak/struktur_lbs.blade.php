@extends('cetak.layout')

@section('isi_cetak')
    <p style="text-align:center;font-weight:bold;margin-bottom:10px;">
        RT {{ $dataset['rt'] }}
        @if ($dataset['kelurahan'] ?? null)
            — Kelurahan {{ $dataset['kelurahan']->nama }}
        @endif
    </p>

    <div style="margin-bottom: 12px;">
        @foreach ($dataset['struktur_lbs'] as $jabatan => $anggota)
            <p style="margin: 4px 0;"><strong>{{ $jabatan }}</strong>:
                {{ $anggota->pluck('nama')->join(', ') }}
            </p>
        @endforeach
    </div>

    <table class="buku">
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
                    <td colspan="{{ count($dataset['kolom']) }}" style="text-align:center;">Belum ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
