@if (auth()->check() && $daftarKelurahanUntukPemilih->isNotEmpty())
    <div class="card mb-3 border-secondary">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
            <span class="small text-muted">Kelurahan aktif (superadmin):</span>
            <form method="post" action="{{ route('kelurahan-aktif.update') }}" class="d-flex flex-wrap align-items-center gap-2 mb-0">
                @csrf
                <select name="kelurahan_id" class="form-select form-select-sm" style="width: auto; min-width: 220px;">
                    @foreach ($daftarKelurahanUntukPemilih as $item)
                        <option value="{{ $item->id }}" @selected($kelurahanAktif && $kelurahanAktif->id === $item->id)>
                            {{ $item->nama }} ({{ $item->kode }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">Terapkan</button>
            </form>
            @if ($kelurahanAktif)
                <span class="small ms-md-2">Menampilkan data: <strong>{{ $kelurahanAktif->nama }}</strong></span>
            @endif
        </div>
    </div>
@endif
