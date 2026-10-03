<?php

use App\Http\Controllers\AgendaSuratController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BukuKunjunganController;
use App\Http\Controllers\BukuTamuController;
use App\Http\Controllers\CetakController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisposisiController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\KegiatanFotoController;
use App\Http\Controllers\OrangController;
use App\Http\Controllers\UserController;
use App\Support\PkkPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    $lihatAnggota = PkkPermission::middleware(PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_ANGGOTA);
    $lihatSurat = PkkPermission::middleware(PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_SURAT);
    $lihatKegiatan = PkkPermission::middleware(
        PkkPermission::LIHAT_BUKU,
        PkkPermission::KELOLA_KEGIATAN,
        PkkPermission::ISI_PRESENSI
    );
    $lihatTamu = PkkPermission::middleware(PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_BUKU_TAMU);
    $lihatKunjungan = PkkPermission::middleware(PkkPermission::LIHAT_BUKU, PkkPermission::KELOLA_BUKU_KUNJUNGAN);
    $lihatKas = PkkPermission::middleware(PkkPermission::LIHAT_KAS, PkkPermission::KELOLA_KAS);
    $lihatInventaris = PkkPermission::middleware(PkkPermission::LIHAT_INVENTARIS, PkkPermission::KELOLA_INVENTARIS);

    $cetakAkses = PkkPermission::middleware(
        PkkPermission::LIHAT_BUKU,
        PkkPermission::VERIFIKASI_BUKU,
        PkkPermission::LIHAT_KAS,
        PkkPermission::KELOLA_KAS,
        PkkPermission::LIHAT_INVENTARIS,
        PkkPermission::KELOLA_INVENTARIS,
    );

    Route::get('cetak/{buku}', [CetakController::class, 'show'])
        ->middleware($cetakAkses)
        ->name('cetak.show');
    Route::get('cetak/{buku}/pdf', [CetakController::class, 'pdf'])
        ->middleware($cetakAkses)
        ->name('cetak.pdf');
    Route::get('export/{buku}', [CetakController::class, 'export'])
        ->middleware($cetakAkses)
        ->name('export.buku');

    Route::get('orang/daftar-anggota', [OrangController::class, 'daftarAnggota'])
        ->middleware($lihatAnggota)
        ->name('orang.daftar-anggota');
    Route::get('orang/{orang}/foto', [OrangController::class, 'foto'])
        ->middleware($lihatAnggota)
        ->name('orang.foto');

    Route::get('orang', [OrangController::class, 'index'])->middleware($lihatAnggota)->name('orang.index');
    Route::get('orang/create', [OrangController::class, 'create'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_ANGGOTA))->name('orang.create');
    Route::post('orang', [OrangController::class, 'store'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_ANGGOTA))->name('orang.store');
    Route::get('orang/{orang}', [OrangController::class, 'show'])->middleware($lihatAnggota)->name('orang.show');
    Route::get('orang/{orang}/edit', [OrangController::class, 'edit'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_ANGGOTA))->name('orang.edit');
    Route::put('orang/{orang}', [OrangController::class, 'update'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_ANGGOTA))->name('orang.update');
    Route::delete('orang/{orang}', [OrangController::class, 'destroy'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_ANGGOTA))->name('orang.destroy');

    Route::get('agenda-surat/{agenda_surat}/berkas', [AgendaSuratController::class, 'berkas'])
        ->middleware($lihatSurat)
        ->name('agenda-surat.berkas');
    Route::post('agenda-surat/{agenda_surat}/disposisi', [DisposisiController::class, 'store'])
        ->middleware(PkkPermission::middleware(PkkPermission::KELOLA_SURAT))
        ->name('agenda-surat.disposisi.store');
    Route::put('disposisi/{disposisi}', [DisposisiController::class, 'update'])
        ->middleware(PkkPermission::middleware(PkkPermission::KELOLA_SURAT))
        ->name('disposisi.update');

    Route::get('agenda-surat', [AgendaSuratController::class, 'index'])->middleware($lihatSurat)->name('agenda-surat.index');
    Route::get('agenda-surat/create', [AgendaSuratController::class, 'create'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_SURAT))->name('agenda-surat.create');
    Route::post('agenda-surat', [AgendaSuratController::class, 'store'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_SURAT))->name('agenda-surat.store');
    Route::get('agenda-surat/{agenda_surat}', [AgendaSuratController::class, 'show'])->middleware($lihatSurat)->name('agenda-surat.show');
    Route::get('agenda-surat/{agenda_surat}/edit', [AgendaSuratController::class, 'edit'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_SURAT))->name('agenda-surat.edit');
    Route::put('agenda-surat/{agenda_surat}', [AgendaSuratController::class, 'update'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_SURAT))->name('agenda-surat.update');
    Route::delete('agenda-surat/{agenda_surat}', [AgendaSuratController::class, 'destroy'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_SURAT))->name('agenda-surat.destroy');

    Route::get('buku-tamu', [BukuTamuController::class, 'index'])->middleware($lihatTamu)->name('buku-tamu.index');
    Route::get('buku-tamu/create', [BukuTamuController::class, 'create'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_TAMU))->name('buku-tamu.create');
    Route::post('buku-tamu', [BukuTamuController::class, 'store'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_TAMU))->name('buku-tamu.store');
    Route::get('buku-tamu/{buku_tamu}', [BukuTamuController::class, 'show'])->middleware($lihatTamu)->name('buku-tamu.show');
    Route::get('buku-tamu/{buku_tamu}/edit', [BukuTamuController::class, 'edit'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_TAMU))->name('buku-tamu.edit');
    Route::put('buku-tamu/{buku_tamu}', [BukuTamuController::class, 'update'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_TAMU))->name('buku-tamu.update');
    Route::delete('buku-tamu/{buku_tamu}', [BukuTamuController::class, 'destroy'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_TAMU))->name('buku-tamu.destroy');

    Route::get('buku-kunjungan', [BukuKunjunganController::class, 'index'])->middleware($lihatKunjungan)->name('buku-kunjungan.index');
    Route::get('buku-kunjungan/create', [BukuKunjunganController::class, 'create'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_KUNJUNGAN))->name('buku-kunjungan.create');
    Route::post('buku-kunjungan', [BukuKunjunganController::class, 'store'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_KUNJUNGAN))->name('buku-kunjungan.store');
    Route::get('buku-kunjungan/{buku_kunjungan}', [BukuKunjunganController::class, 'show'])->middleware($lihatKunjungan)->name('buku-kunjungan.show');
    Route::get('buku-kunjungan/{buku_kunjungan}/edit', [BukuKunjunganController::class, 'edit'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_KUNJUNGAN))->name('buku-kunjungan.edit');
    Route::put('buku-kunjungan/{buku_kunjungan}', [BukuKunjunganController::class, 'update'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_KUNJUNGAN))->name('buku-kunjungan.update');
    Route::delete('buku-kunjungan/{buku_kunjungan}', [BukuKunjunganController::class, 'destroy'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_BUKU_KUNJUNGAN))->name('buku-kunjungan.destroy');

    Route::get('inventaris', [InventarisController::class, 'index'])->middleware($lihatInventaris)->name('inventaris.index');
    Route::get('inventaris/create', [InventarisController::class, 'create'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_INVENTARIS))->name('inventaris.create');
    Route::post('inventaris', [InventarisController::class, 'store'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_INVENTARIS))->name('inventaris.store');
    Route::get('inventaris/{inventaris_barang}', [InventarisController::class, 'show'])->middleware($lihatInventaris)->name('inventaris.show');
    Route::get('inventaris/{inventaris_barang}/edit', [InventarisController::class, 'edit'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_INVENTARIS))->name('inventaris.edit');
    Route::put('inventaris/{inventaris_barang}', [InventarisController::class, 'update'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_INVENTARIS))->name('inventaris.update');
    Route::delete('inventaris/{inventaris_barang}', [InventarisController::class, 'destroy'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_INVENTARIS))->name('inventaris.destroy');

    Route::get('kas/rekap', [KasController::class, 'rekap'])->middleware($lihatKas)->name('kas.rekap');
    Route::get('kas/rekap/pdf', [KasController::class, 'rekapPdf'])->middleware($lihatKas)->name('kas.rekap.pdf');
    Route::get('kas', [KasController::class, 'index'])->middleware($lihatKas)->name('kas.index');
    Route::post('kas/tutup-buku', [KasController::class, 'storeTutupBuku'])
        ->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KAS))
        ->name('kas.tutup-buku.store');
    Route::delete('kas/tutup-buku/{kasTutupBuku}', [KasController::class, 'destroyTutupBuku'])
        ->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KAS))
        ->name('kas.tutup-buku.destroy');
    Route::get('kas/create', [KasController::class, 'create'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KAS))->name('kas.create');
    Route::post('kas', [KasController::class, 'store'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KAS))->name('kas.store');
    Route::post('kas/saldo-awal', [KasController::class, 'storeSaldoAwal'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KAS))->name('kas.saldo-awal.store');
    Route::get('kas/{kasTransaksi}', [KasController::class, 'show'])->middleware($lihatKas)->name('kas.show');
    Route::get('kas/{kasTransaksi}/edit', [KasController::class, 'edit'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KAS))->name('kas.edit');
    Route::put('kas/{kasTransaksi}', [KasController::class, 'update'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KAS))->name('kas.update');
    Route::delete('kas/{kasTransaksi}', [KasController::class, 'destroy'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KAS))->name('kas.destroy');

    Route::get('kegiatan/cari-orang', [KegiatanController::class, 'cariOrang'])
        ->middleware($lihatKegiatan)
        ->name('kegiatan.cari-orang');
    Route::post('kegiatan/{kegiatan}/presensi', [KegiatanController::class, 'simpanPresensi'])
        ->middleware(PkkPermission::middleware(PkkPermission::ISI_PRESENSI, PkkPermission::KELOLA_KEGIATAN))
        ->name('kegiatan.presensi.store');
    Route::patch('kegiatan/{kegiatan}/presensi/{presensi}', [KegiatanController::class, 'ubahPresensi'])
        ->middleware(PkkPermission::middleware(PkkPermission::ISI_PRESENSI, PkkPermission::KELOLA_KEGIATAN))
        ->name('kegiatan.presensi.update');
    Route::post('kegiatan/{kegiatan}/notulen', [KegiatanController::class, 'simpanNotulen'])
        ->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KEGIATAN))
        ->name('kegiatan.notulen.store');
    Route::post('kegiatan/{kegiatan}/foto', [KegiatanFotoController::class, 'store'])
        ->middleware(PkkPermission::middleware(PkkPermission::ISI_PRESENSI, PkkPermission::KELOLA_KEGIATAN))
        ->name('kegiatan.foto.store');
    Route::delete('kegiatan-foto/{foto}', [KegiatanFotoController::class, 'destroy'])
        ->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KEGIATAN))
        ->name('kegiatan-foto.destroy');
    Route::get('kegiatan-foto/{foto}/berkas', [KegiatanFotoController::class, 'berkas'])
        ->name('kegiatan-foto.berkas');

    Route::get('kegiatan', [KegiatanController::class, 'index'])->middleware($lihatKegiatan)->name('kegiatan.index');
    Route::get('kegiatan/create', [KegiatanController::class, 'create'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KEGIATAN))->name('kegiatan.create');
    Route::post('kegiatan', [KegiatanController::class, 'store'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KEGIATAN))->name('kegiatan.store');
    Route::get('kegiatan/{kegiatan}', [KegiatanController::class, 'show'])->middleware($lihatKegiatan)->name('kegiatan.show');
    Route::get('kegiatan/{kegiatan}/edit', [KegiatanController::class, 'edit'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KEGIATAN))->name('kegiatan.edit');
    Route::put('kegiatan/{kegiatan}', [KegiatanController::class, 'update'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KEGIATAN))->name('kegiatan.update');
    Route::delete('kegiatan/{kegiatan}', [KegiatanController::class, 'destroy'])->middleware(PkkPermission::middleware(PkkPermission::KELOLA_KEGIATAN))->name('kegiatan.destroy');

    Route::middleware(PkkPermission::middleware(PkkPermission::KELOLA_PENGGUNA))->group(function () {
        Route::resource('pengguna', UserController::class)->except(['show', 'destroy']);
    });

    Route::get('audit', [AuditController::class, 'index'])
        ->middleware(PkkPermission::middleware(PkkPermission::LIHAT_AUDIT))
        ->name('audit.index');
});
