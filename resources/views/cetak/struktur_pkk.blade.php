@extends('cetak.layout')

@section('isi_cetak')
    <style>
        .bagan { margin-top: 16px; text-align: center; }
        .bagan .kotak {
            border: 1px solid #333;
            padding: 6px 10px;
            display: inline-block;
            min-width: 140px;
            margin: 4px;
            font-size: 8pt;
        }
        .bagan .tingkat { margin: 8px 0; }
        .bagan .cabang { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
        .daftar-struktur h4 { font-size: 9pt; margin: 10px 0 4px; text-transform: uppercase; }
        .daftar-struktur ul { margin: 0 0 8px 16px; padding: 0; font-size: 8.5pt; }
    </style>

    <div class="daftar-struktur">
        @php
            $urutanJabatan = ['PEMBINA', 'KETUA', 'SEKRETARIS', 'BENDAHARA'];
        @endphp
        @foreach ($urutanJabatan as $jab)
            @if (!empty($dataset['struktur_tp'][$jab]))
                <h4>{{ $jab }}</h4>
                <ul>
                    @foreach ($dataset['struktur_tp'][$jab] as $row)
                        <li>{{ $row->nama }}</li>
                    @endforeach
                </ul>
            @endif
        @endforeach

        @foreach ($dataset['struktur_tp'] as $jabatan => $anggota)
            @if (!in_array($jabatan, $urutanJabatan, true))
                <h4>{{ $jabatan }}</h4>
                <ul>
                    @foreach ($anggota as $row)
                        <li>{{ $row->nama }}</li>
                    @endforeach
                </ul>
            @endif
        @endforeach

        @foreach ($dataset['struktur_pokja'] as $pokjaId => $anggota)
            @php $pokja = $anggota->first()?->pokja; @endphp
            <h4>{{ $pokja ? 'Pokja '.$pokja->kode.' — '.$pokja->nama : 'Pokja' }}</h4>
            <ul>
                @foreach ($anggota as $row)
                    <li>{{ $row->jabatan }}: {{ $row->nama }}</li>
                @endforeach
            </ul>
        @endforeach
    </div>

    <div class="bagan">
        <div class="tingkat">
            @foreach ($urutanJabatan as $jab)
                @if (!empty($dataset['struktur_tp'][$jab]))
                    <div class="kotak">
                        <strong>{{ $jab }}</strong><br>
                        {{ $dataset['struktur_tp'][$jab]->first()->nama }}
                    </div>
                @endif
            @endforeach
        </div>
        <div class="tingkat cabang">
            @foreach ($dataset['struktur_pokja'] as $anggota)
                @php
                    $pokja = $anggota->first()?->pokja;
                    $ketua = $anggota->first();
                @endphp
                <div>
                    <div class="kotak">
                        <strong>{{ $pokja ? 'Pokja '.$pokja->kode : 'Pokja' }}</strong><br>
                        {{ $ketua?->nama }}
                    </div>
                    @if ($anggota->count() > 1)
                        <div class="cabang" style="margin-top:4px;">
                            @foreach ($anggota->slice(1) as $row)
                                <div class="kotak" style="min-width:100px;">{{ $row->nama }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <table class="buku" style="margin-top: 14px;">
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
