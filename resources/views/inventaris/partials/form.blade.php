@php
    use App\Models\InventarisBarang;

    $pokjaId = old('pokja_id', $inventarisBarang->pokja_id ?? null);
    if ($pokjaId === null && isset($buku) && str_starts_with((string) $buku, 'pokja-')) {
        $pokjaId = (int) substr($buku, 6);
    }
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="pokja_id">Buku</label>
        <select name="pokja_id" id="pokja_id" class="form-select">
            <option value="">Kelurahan</option>
            @foreach ($pokjaList as $pokja)
                <option value="{{ $pokja->id }}" @selected((string) $pokjaId === (string) $pokja->id)>
                    Pokja {{ $pokja->kode }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="nama_barang">Nama barang</label>
        <input type="text" name="nama_barang" id="nama_barang" class="form-control" required
            value="{{ old('nama_barang', $inventarisBarang->nama_barang ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="asal_barang">Asal barang</label>
        <input type="text" name="asal_barang" id="asal_barang" class="form-control"
            value="{{ old('asal_barang', $inventarisBarang->asal_barang ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="tanggal_terima">Tanggal penerimaan / pembelian</label>
        <input type="date" name="tanggal_terima" id="tanggal_terima" class="form-control" required
            value="{{ old('tanggal_terima', optional($inventarisBarang->tanggal_terima ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="jumlah">Jumlah</label>
        <input type="number" name="jumlah" id="jumlah" class="form-control" required min="1" step="1"
            value="{{ old('jumlah', $inventarisBarang->jumlah ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="tempat_penyimpanan">Tempat penyimpanan</label>
        <input type="text" name="tempat_penyimpanan" id="tempat_penyimpanan" class="form-control"
            value="{{ old('tempat_penyimpanan', $inventarisBarang->tempat_penyimpanan ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="kondisi">Kondisi barang</label>
        <select name="kondisi" id="kondisi" class="form-select" required>
            <option value="">— Pilih —</option>
            @foreach (InventarisBarang::kondisiNilai() as $nilai)
                <option value="{{ $nilai }}" @selected(old('kondisi', $inventarisBarang->kondisi ?? '') === $nilai)>
                    {{ InventarisBarang::labelKondisiUntuk($nilai) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="keterangan">Keterangan</label>
        <textarea name="keterangan" id="keterangan" class="form-control" rows="2">{{ old('keterangan', $inventarisBarang->keterangan ?? '') }}</textarea>
    </div>
</div>
