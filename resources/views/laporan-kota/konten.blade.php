@php
    use App\Models\InventarisBarang;
    use App\Services\LaporanKotaService;

    $laporan = $dataset['laporan'] ?? [];
    $identitas = $laporan['identitas'] ?? [];
    $keanggotaan = $laporan['keanggotaan'] ?? [];
    $kegiatan = $laporan['kegiatan'] ?? [];
    $agenda = $laporan['agenda_surat'] ?? [];
    $keuangan = $laporan['keuangan'] ?? [];
    $inventaris = $laporan['inventaris'] ?? [];
    $programKerja = $laporan['program_kerja'] ?? [];
    $struktur = $laporan['struktur'] ?? [];
@endphp

<style>
    .laporan-kota section { margin-bottom: 14px; }
    .laporan-kota h3 { font-size: 10pt; margin: 0 0 6px; text-transform: uppercase; }
    .laporan-kota table.ringkas { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .laporan-kota table.ringkas th,
    .laporan-kota table.ringkas td { border: 1px solid #333; padding: 3px 5px; font-size: 8.5pt; }
    .laporan-kota table.ringkas th { background: #f5f5f5; text-align: left; }
    .laporan-kota dl.meta { margin: 0; font-size: 9pt; }
    .laporan-kota dl.meta dt { font-weight: bold; display: inline; }
    .laporan-kota dl.meta dd { display: inline; margin: 0 0 0 4px; }
    .laporan-kota dl.meta div { margin-bottom: 3px; }
    .laporan-kota .angka { text-align: right; }
</style>

<div class="laporan-kota">
    <section>
        <h3>A. Identitas</h3>
        <dl class="meta">
            <div><dt>Kelurahan:</dt><dd>{{ $identitas['nama_kelurahan'] ?? '—' }}</dd></div>
            <div><dt>Kecamatan / Kota:</dt><dd>{{ $identitas['kecamatan'] ?? '—' }}, {{ $identitas['kota'] ?? '—' }}</dd></div>
            <div><dt>Periode:</dt><dd>{{ $identitas['periode'] ?? '—' }}</dd></div>
            <div><dt>Tanggal cetak:</dt><dd>{{ $identitas['tanggal_cetak'] ?? '—' }}</dd></div>
        </dl>
    </section>

    <section>
        <h3>B. Keanggotaan</h3>
        <table class="ringkas">
            <tr><th>Jumlah orang terdaftar</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($keanggotaan['jumlah_orang_terdaftar'] ?? 0)) }}</td></tr>
            <tr><th>Jumlah kader aktif</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($keanggotaan['jumlah_kader'] ?? 0)) }}</td></tr>
        </table>
        @if (!empty($keanggotaan['keanggotaan_aktif_per_pokja']))
            <table class="ringkas">
                <thead>
                    <tr><th>Pokja</th><th class="angka">Keanggotaan aktif</th></tr>
                </thead>
                <tbody>
                    @foreach ($keanggotaan['keanggotaan_aktif_per_pokja'] as $pokja)
                        <tr>
                            <td>Pokja {{ $pokja['pokja_kode'] }} — {{ $pokja['pokja_nama'] }}</td>
                            <td class="angka">{{ LaporanKotaService::formatAngka((int) $pokja['jumlah_aktif']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section>
        <h3>C. Kegiatan</h3>
        <p style="font-size:9pt;margin:0 0 6px;">
            Jumlah kegiatan: <strong>{{ LaporanKotaService::formatAngka((int) ($kegiatan['jumlah_kegiatan'] ?? 0)) }}</strong>;
            total peserta hadir: <strong>{{ LaporanKotaService::formatAngka((int) ($kegiatan['total_peserta_hadir'] ?? 0)) }}</strong>
        </p>
        <table class="ringkas">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama kegiatan</th>
                    <th>Tempat</th>
                    <th class="angka">Hadir</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kegiatan['daftar'] ?? [] as $baris)
                    <tr>
                        <td>{{ $baris['tanggal'] }}</td>
                        <td>{{ $baris['nama'] }}</td>
                        <td>{{ $baris['tempat'] }}</td>
                        <td class="angka">{{ LaporanKotaService::formatAngka((int) $baris['jumlah_hadir']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Tidak ada kegiatan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section>
        <h3>D. Agenda surat</h3>
        <table class="ringkas">
            <tr><th>Surat masuk</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($agenda['surat_masuk'] ?? 0)) }}</td></tr>
            <tr><th>Surat keluar</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($agenda['surat_keluar'] ?? 0)) }}</td></tr>
            <tr><th>Disposisi belum selesai</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($agenda['disposisi_belum_selesai'] ?? 0)) }}</td></tr>
        </table>
    </section>

    <section>
        <h3>E. Keuangan kas (tingkat kelurahan)</h3>
        <table class="ringkas">
            <thead>
                <tr>
                    <th>Pos</th>
                    <th class="angka">Saldo awal</th>
                    <th class="angka">Pemasukan</th>
                    <th class="angka">Pengeluaran</th>
                    <th class="angka">Saldo akhir</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tunai</td>
                    <td class="angka">{{ $keuangan['tunai']['saldo_awal'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['tunai']['masuk'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['tunai']['keluar'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['tunai']['saldo_akhir'] ?? 'Rp 0' }}</td>
                </tr>
                <tr>
                    <td>Bank</td>
                    <td class="angka">{{ $keuangan['bank']['saldo_awal'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['bank']['masuk'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['bank']['keluar'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['bank']['saldo_akhir'] ?? 'Rp 0' }}</td>
                </tr>
                <tr>
                    <td><strong>Total</strong></td>
                    <td class="angka">{{ $keuangan['total']['saldo_awal'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['total']['masuk'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['total']['keluar'] ?? 'Rp 0' }}</td>
                    <td class="angka">{{ $keuangan['total']['saldo_akhir'] ?? 'Rp 0' }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <section>
        <h3>F. Inventaris</h3>
        <table class="ringkas">
            <tr><th>Jumlah jenis barang</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($inventaris['jumlah_jenis_barang'] ?? 0)) }}</td></tr>
            <tr><th>Total unit</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($inventaris['total_unit'] ?? 0)) }}</td></tr>
            <tr><th>Kondisi baik</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($inventaris['per_kondisi'][InventarisBarang::KONDISI_BAIK] ?? 0)) }}</td></tr>
            <tr><th>Rusak ringan</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($inventaris['per_kondisi'][InventarisBarang::KONDISI_RUSAK_RINGAN] ?? 0)) }}</td></tr>
            <tr><th>Rusak berat</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($inventaris['per_kondisi'][InventarisBarang::KONDISI_RUSAK_BERAT] ?? 0)) }}</td></tr>
        </table>
    </section>

    <section>
        <h3>G. Program kerja</h3>
        <table class="ringkas">
            <tr><th>Jumlah butir program kerja</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($programKerja['jumlah_butir'] ?? 0)) }}</td></tr>
            <tr><th>Sudah punya realisasi</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($programKerja['jumlah_dengan_realisasi'] ?? 0)) }}</td></tr>
            <tr><th>Jumlah kegiatan tertaut</th><td class="angka">{{ LaporanKotaService::formatAngka((int) ($programKerja['jumlah_kegiatan_tertaut'] ?? 0)) }}</td></tr>
        </table>
    </section>

    <section>
        <h3>H. Struktur pengurus TP PKK</h3>
        <table class="ringkas">
            <tr><th>Ketua</th><td>{{ $struktur['ketua'] ?? '—' }}</td></tr>
            <tr><th>Sekretaris</th><td>{{ $struktur['sekretaris'] ?? '—' }}</td></tr>
            <tr><th>Bendahara</th><td>{{ $struktur['bendahara'] ?? '—' }}</td></tr>
        </table>
    </section>
</div>
