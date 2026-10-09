@php
    $query = array_merge(
        request()->only(['tahun', 'buku', 'pokja_id', 'unit', 'kegiatan', 'dari', 'sampai', 'bulan', 'jenis', 'q', 'status_tindak', 'aktif']),
        $queryTambahan ?? [],
    );
@endphp
<div class="d-flex flex-wrap gap-2 align-items-center cetak-toolbar">
    @if (!empty($toolbarLabel))
        <span class="small text-muted me-1">{{ $toolbarLabel }}:</span>
    @endif
    <a href="{{ route('cetak.show', array_merge(['buku' => $kodeBuku], $query)) }}"
        class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">
        <i class="bi bi-printer"></i> Cetak
    </a>
    <a href="{{ route('cetak.pdf', array_merge(['buku' => $kodeBuku], $query)) }}"
        class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-file-earmark-pdf"></i> Unduh PDF
    </a>
    <a href="{{ route('export.buku', array_merge(['buku' => $kodeBuku], $query)) }}"
        class="btn btn-outline-success btn-sm">
        <i class="bi bi-file-earmark-excel"></i> Export Excel
    </a>
</div>
