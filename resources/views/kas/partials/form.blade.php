@php
    use App\Models\KasTransaksi;
    $pokjaId = old('pokja_id', $transaksi?->pokja_id);
    if ($pokjaId === null && isset($buku) && str_starts_with($buku, 'pokja-')) {
        $pokjaId = (int) substr($buku, 6);
    }
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="pokja_id">Buku</label>
        <select name="pokja_id" id="pokja_id" class="form-select @error('pokja_id') is-invalid @enderror">
            <option value="" @selected($pokjaId === null || $pokjaId === '')>Kelurahan (Kas Umum)</option>
            @foreach ($pokjaList as $pokja)
                <option value="{{ $pokja->id }}" @selected((string) $pokjaId === (string) $pokja->id)>
                    Pokja {{ $pokja->kode }}
                </option>
            @endforeach
        </select>
        @error('pokja_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="jenis">Jenis</label>
        <select name="jenis" id="jenis" class="form-select @error('jenis') is-invalid @enderror" required>
            <option value="{{ KasTransaksi::JENIS_MASUK }}" @selected(old('jenis', $transaksi?->jenis) === KasTransaksi::JENIS_MASUK)>
                Pemasukan
            </option>
            <option value="{{ KasTransaksi::JENIS_KELUAR }}" @selected(old('jenis', $transaksi?->jenis) === KasTransaksi::JENIS_KELUAR)>
                Pengeluaran
            </option>
        </select>
        @error('jenis')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label" for="pos">Tempat uang</label>
        <select name="pos" id="pos" class="form-select @error('pos') is-invalid @enderror" required>
            <option value="{{ KasTransaksi::POS_TUNAI }}" @selected(old('pos', $transaksi?->pos) === KasTransaksi::POS_TUNAI)>
                Tunai
            </option>
            <option value="{{ KasTransaksi::POS_BANK }}" @selected(old('pos', $transaksi?->pos) === KasTransaksi::POS_BANK)>
                Bank
            </option>
        </select>
        @error('pos')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="tanggal">Tanggal</label>
        <input type="date" name="tanggal" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror"
            value="{{ old('tanggal', $transaksi?->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
        @error('tanggal')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="jumlah">Jumlah (Rp)</label>
        <input type="number" name="jumlah" id="jumlah" step="0.01" min="0.01"
            class="form-control @error('jumlah') is-invalid @enderror"
            value="{{ old('jumlah', $transaksi?->jumlah) }}" required>
        @error('jumlah')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label" for="no_bukti">Nomor bukti kas</label>
        <input type="text" name="no_bukti" id="no_bukti" class="form-control @error('no_bukti') is-invalid @enderror"
            value="{{ old('no_bukti', $transaksi?->no_bukti) }}">
        @error('no_bukti')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="sumber_dana">Sumber dana</label>
        <input type="text" name="sumber_dana" id="sumber_dana" class="form-control @error('sumber_dana') is-invalid @enderror"
            value="{{ old('sumber_dana', $transaksi?->sumber_dana) }}">
        @error('sumber_dana')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="uraian">Uraian</label>
        <textarea name="uraian" id="uraian" rows="3" class="form-control @error('uraian') is-invalid @enderror"
            required>{{ old('uraian', $transaksi?->uraian) }}</textarea>
        @error('uraian')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
