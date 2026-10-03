<?php

namespace App\Services;

use App\Models\AgendaSurat;
use App\Models\Keanggotaan;
use App\Models\Kelurahan;
use App\Models\Orang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class Buku2026Importer
{
    public const SHEET_ANGGOTA_2026 = 'BUKU DAFTAR ANGGOTA (2026)';

    public const SHEET_TP_KADER = 'DAFTAR ANGGOTA TP PKK & KADER';

    public const SHEET_AGENDA = 'BUKU AGENDA SURAT (2)';

    private const IMPORT_YEAR = 2026;

    /** @var array<string, string> */
    private const BULAN_ID = [
        'januari' => '01',
        'jan' => '01',
        'februari' => '02',
        'feb' => '02',
        'maret' => '03',
        'mar' => '03',
        'april' => '04',
        'apr' => '04',
        'mei' => '05',
        'juni' => '06',
        'jun' => '06',
        'juli' => '07',
        'jul' => '07',
        'agustus' => '08',
        'agu' => '08',
        'ags' => '08',
        'september' => '09',
        'sep' => '09',
        'sept' => '09',
        'oktober' => '10',
        'okt' => '10',
        'november' => '11',
        'nov' => '11',
        'nopember' => '11',
        'desember' => '12',
        'des' => '12',
    ];

    public function __construct(
        private readonly bool $dryRun = true,
    ) {}

    public function run(string $filePath): Buku2026ImportReport
    {
        $report = new Buku2026ImportReport;

        if (! is_readable($filePath)) {
            throw new \InvalidArgumentException("Berkas tidak dapat dibaca: {$filePath}");
        }

        $kelurahan = Kelurahan::query()->where('kode', 'GSI')->first();
        if ($kelurahan === null) {
            throw new \RuntimeException('Kelurahan GSI tidak ditemukan. Jalankan MasterSeeder terlebih dahulu.');
        }

        $spreadsheet = IOFactory::load($filePath);

        $this->importAnggota2026($spreadsheet->getSheetByName(self::SHEET_ANGGOTA_2026), $kelurahan, $report);
        $this->importTpKader($spreadsheet->getSheetByName(self::SHEET_TP_KADER), $kelurahan, $report);
        $this->importAgenda($spreadsheet->getSheetByName(self::SHEET_AGENDA), $kelurahan, $report);

        return $report;
    }

    public static function parseTanggal(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (Throwable) {
                return null;
            }
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (str_contains($text, '/')) {
            $beforeSlash = trim(explode('/', $text, 2)[0]);
            if ($beforeSlash !== $text) {
                $parsed = self::parseTanggal($beforeSlash);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $text)->startOfDay();
            } catch (Throwable) {
                return null;
            }
        }

        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})$/', $text, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            if ($year < 100) {
                $year += $year >= 70 ? 1900 : 2000;
            }

            return self::safeDate($year, $month, $day);
        }

        if (preg_match('/^(\d{1,2})\s*[-\/]\s*([A-Za-zÀ-ÿ]+)\s*[-\/]\s*(\d{2,4})$/u', $text, $m)) {
            $day = (int) $m[1];
            $monthKey = mb_strtolower(trim($m[2]));
            $year = (int) $m[3];
            if ($year < 100) {
                $year += 2000;
            }
            $month = self::bulanKeAngka($monthKey);
            if ($month === null) {
                return null;
            }

            return self::safeDate($year, (int) $month, $day);
        }

        if (preg_match('/^(\d{1,2})\s+([A-Za-zÀ-ÿ]+)\s+(\d{4})$/u', $text, $m)) {
            $month = self::bulanKeAngka(mb_strtolower(trim($m[2])));
            if ($month === null) {
                return null;
            }

            return self::safeDate((int) $m[3], (int) $month, (int) $m[1]);
        }

        try {
            return Carbon::parse($text)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private static function bulanKeAngka(string $nama): ?string
    {
        $nama = preg_replace('/[^a-z]/', '', $nama) ?? $nama;

        return self::BULAN_ID[$nama] ?? null;
    }

    private static function safeDate(int $year, int $month, int $day): ?Carbon
    {
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return Carbon::createFromDate($year, $month, $day)->startOfDay();
    }

    private function importAnggota2026(?Worksheet $sheet, Kelurahan $kelurahan, Buku2026ImportReport $report): void
    {
        if ($sheet === null) {
            $report->failed++;
            $report->failedRows[] = [
                'sheet' => self::SHEET_ANGGOTA_2026,
                'row' => 0,
                'side' => 'anggota',
                'reason' => 'Sheet tidak ditemukan',
            ];

            return;
        }

        $startRow = $this->findRowAfterColumnNumbers($sheet, 'NAMA');

        for ($row = $startRow; $row <= (int) $sheet->getHighestRow(); $row++) {
            $nama = $this->cellString($sheet, 'C', $row);
            $jabatanAwal = $this->cellString($sheet, 'D', $row);
            if ($nama === '') {
                if ($jabatanAwal !== '' || $this->cellString($sheet, 'E', $row) !== '') {
                    $report->read++;
                    $this->recordSkip($report, self::SHEET_ANGGOTA_2026, $row, 'anggota', 'nama kosong');
                }

                continue;
            }

            if ($this->shouldSkipNama($nama)) {
                continue;
            }

            $report->read++;

            $payload = [
                'nama' => $nama,
                'jabatan' => $jabatanAwal,
                'jenis_kelamin' => $this->normalizeJenisKelamin($this->cellString($sheet, 'E', $row)),
                'tempat_lahir' => $this->nullableString($this->cellString($sheet, 'F', $row)),
                'tanggal_lahir_raw' => $this->cellValue($sheet, 'G', $row),
                'status_perkawinan' => $this->nullableString($this->cellString($sheet, 'H', $row)),
                'alamat' => $this->nullableString($this->cellString($sheet, 'I', $row)),
                'pendidikan' => $this->nullableString($this->cellString($sheet, 'J', $row)),
                'pekerjaan' => $this->nullableString($this->cellString($sheet, 'K', $row)),
                'catatan' => $this->nullableString($this->cellString($sheet, 'L', $row)),
                'no_registrasi' => null,
                'jenis_keanggotaan' => Keanggotaan::JENIS_TP_PKK,
            ];

            $this->processOrangKeanggotaan(
                $kelurahan,
                $report,
                self::SHEET_ANGGOTA_2026,
                $row,
                'anggota',
                $payload,
            );
        }
    }

    private function importTpKader(?Worksheet $sheet, Kelurahan $kelurahan, Buku2026ImportReport $report): void
    {
        if ($sheet === null) {
            $report->failed++;
            $report->failedRows[] = [
                'sheet' => self::SHEET_TP_KADER,
                'row' => 0,
                'side' => 'tp_kader',
                'reason' => 'Sheet tidak ditemukan',
            ];

            return;
        }

        $startRow = $this->findRowAfterColumnNumbers($sheet, 'NAMA');

        for ($row = $startRow; $row <= (int) $sheet->getHighestRow(); $row++) {
            $nama = $this->cellString($sheet, 'D', $row);
            $jabatanAwal = $this->cellString($sheet, 'F', $row);
            if ($nama === '') {
                if ($jabatanAwal !== '' || $this->cellString($sheet, 'E', $row) !== '') {
                    $report->read++;
                    $this->recordSkip($report, self::SHEET_TP_KADER, $row, 'tp_kader', 'nama kosong');
                }

                continue;
            }

            if ($this->shouldSkipNama($nama)) {
                continue;
            }

            $report->read++;

            $markerUmum = $this->cellString($sheet, 'G', $row);
            $markerKhusus = $this->cellString($sheet, 'H', $row);
            $jenis = $this->jenisDariPenanda($markerUmum, $markerKhusus);

            $jabatan = $this->cellString($sheet, 'F', $row);
            if ($jabatan === '' && $jenis !== Keanggotaan::JENIS_TP_PKK) {
                $jabatan = match ($jenis) {
                    Keanggotaan::JENIS_KADER_UMUM => 'Kader Umum',
                    Keanggotaan::JENIS_KADER_KHUSUS => 'Kader Khusus',
                    default => 'Anggota TP PKK',
                };
            }

            $payload = [
                'nama' => $nama,
                'jabatan' => $jabatan,
                'jenis_kelamin' => $this->normalizeJenisKelamin($this->cellString($sheet, 'E', $row)),
                'tempat_lahir' => null,
                'tanggal_lahir_raw' => $this->cellValue($sheet, 'I', $row),
                'status_perkawinan' => $this->nullableString($this->cellString($sheet, 'J', $row)),
                'alamat' => $this->nullableString($this->cellString($sheet, 'K', $row)),
                'pendidikan' => $this->nullableString($this->cellString($sheet, 'L', $row)),
                'pekerjaan' => $this->nullableString($this->cellString($sheet, 'M', $row)),
                'catatan' => $this->nullableString($this->cellString($sheet, 'N', $row)),
                'no_registrasi' => $this->nullableString($this->cellString($sheet, 'C', $row)),
                'jenis_keanggotaan' => $jenis,
            ];

            $this->processOrangKeanggotaan(
                $kelurahan,
                $report,
                self::SHEET_TP_KADER,
                $row,
                'tp_kader',
                $payload,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function processOrangKeanggotaan(
        Kelurahan $kelurahan,
        Buku2026ImportReport $report,
        string $sheetName,
        int $row,
        string $side,
        array $payload,
    ): void {
        $nama = $payload['nama'];
        if ($this->shouldSkipNama($nama)) {
            $this->recordSkip($report, $sheetName, $row, $side, 'nama kosong');

            return;
        }

        $jenisKelamin = $payload['jenis_kelamin'];
        if ($jenisKelamin === null) {
            $this->recordSkip($report, $sheetName, $row, $side, 'jenis kelamin kosong atau tidak valid');

            return;
        }

        $tanggalLahir = self::parseTanggal($payload['tanggal_lahir_raw']);
        $alamat = $payload['alamat'];
        $jabatan = trim((string) ($payload['jabatan'] ?? ''));
        $jenisKeanggotaan = $payload['jenis_keanggotaan'];

        if ($jabatan === '') {
            $this->recordSkip($report, $sheetName, $row, $side, 'jabatan kosong');

            return;
        }

        $report->addPreview([
            'sheet' => $sheetName,
            'baris' => (string) $row,
            'nama' => $nama,
            'jabatan' => $jabatan,
            'jenis' => $jenisKeanggotaan,
        ]);

        $existingOrang = $this->findOrang($kelurahan->id, $nama, $tanggalLahir, $alamat);
        $existingKeanggotaan = $existingOrang !== null
            ? $this->findKeanggotaan($existingOrang->id, $jenisKeanggotaan, $jabatan)
            : null;

        if ($this->dryRun) {
            if ($existingOrang === null || $existingKeanggotaan === null) {
                $report->created++;
            } else {
                $report->updated++;
            }

            return;
        }

        try {
            DB::transaction(function () use (
                $kelurahan,
                $report,
                $sheetName,
                $row,
                $side,
                $payload,
                $nama,
                $jenisKelamin,
                $tanggalLahir,
                $alamat,
                $jabatan,
                $jenisKeanggotaan,
                $existingOrang,
                $existingKeanggotaan,
            ): void {
                $orangBaru = false;
                $keanggotaanBaru = false;
                $orangDiubah = false;
                $keanggotaanDiubah = false;
                $orang = $existingOrang;

                if ($orang === null) {
                    $orang = Orang::query()->create([
                        'kelurahan_id' => $kelurahan->id,
                        'nama' => $nama,
                        'jenis_kelamin' => $jenisKelamin,
                        'tempat_lahir' => $payload['tempat_lahir'],
                        'tanggal_lahir' => $tanggalLahir,
                        'status_perkawinan' => $payload['status_perkawinan'],
                        'alamat' => $alamat,
                        'pendidikan' => $payload['pendidikan'],
                        'pekerjaan' => $payload['pekerjaan'],
                        'catatan' => $payload['catatan'],
                    ]);
                    $orangBaru = true;
                } else {
                    $orang->fill([
                        'jenis_kelamin' => $jenisKelamin,
                        'tempat_lahir' => $payload['tempat_lahir'] ?? $orang->tempat_lahir,
                        'tanggal_lahir' => $tanggalLahir ?? $orang->tanggal_lahir,
                        'status_perkawinan' => $payload['status_perkawinan'] ?? $orang->status_perkawinan,
                        'alamat' => $alamat ?? $orang->alamat,
                        'pendidikan' => $payload['pendidikan'] ?? $orang->pendidikan,
                        'pekerjaan' => $payload['pekerjaan'] ?? $orang->pekerjaan,
                        'catatan' => $payload['catatan'] ?? $orang->catatan,
                    ]);
                    if ($orang->isDirty()) {
                        $orang->save();
                        $orangDiubah = true;
                    }
                }

                $noReg = $payload['no_registrasi'];
                if ($existingKeanggotaan === null) {
                    Keanggotaan::query()->create([
                        'orang_id' => $orang->id,
                        'kelurahan_id' => $kelurahan->id,
                        'jenis' => $jenisKeanggotaan,
                        'jabatan' => $jabatan,
                        'no_registrasi' => $noReg,
                        'is_aktif' => true,
                    ]);
                    $keanggotaanBaru = true;
                } else {
                    $existingKeanggotaan->fill([
                        'no_registrasi' => $noReg ?? $existingKeanggotaan->no_registrasi,
                        'is_aktif' => true,
                    ]);
                    if ($existingKeanggotaan->isDirty()) {
                        $existingKeanggotaan->save();
                        $keanggotaanDiubah = true;
                    }
                }

                if ($orangBaru || $keanggotaanBaru) {
                    $report->created++;
                } elseif ($orangDiubah || $keanggotaanDiubah) {
                    $report->updated++;
                } else {
                    $report->skipped++;
                    $this->recordSkip($report, $sheetName, $row, $side, 'sudah ada tanpa perubahan');
                }
            });
        } catch (Throwable $e) {
            $report->failed++;
            $report->failedRows[] = [
                'sheet' => $sheetName,
                'row' => $row,
                'side' => $side,
                'reason' => mb_substr($e->getMessage(), 0, 200),
            ];
        }
    }

    private function importAgenda(?Worksheet $sheet, Kelurahan $kelurahan, Buku2026ImportReport $report): void
    {
        if ($sheet === null) {
            $report->failed++;
            $report->failedRows[] = [
                'sheet' => self::SHEET_AGENDA,
                'row' => 0,
                'side' => 'agenda',
                'reason' => 'Sheet tidak ditemukan',
            ];

            return;
        }

        for ($row = 9; $row <= (int) $sheet->getHighestRow(); $row++) {
            $noMasuk = $this->cellValue($sheet, 'B', $row);
            if ($this->isDataNumber($noMasuk)) {
                $this->processAgendaMasuk($sheet, $kelurahan, $report, $row);
            }

            $noSuratKeluar = $this->cellString($sheet, 'K', $row);
            if ($noSuratKeluar !== '') {
                $this->processAgendaKeluar($sheet, $kelurahan, $report, $row);
            }
        }
    }

    private function processAgendaMasuk(
        Worksheet $sheet,
        Kelurahan $kelurahan,
        Buku2026ImportReport $report,
        int $row,
    ): void {
        $report->read++;

        $tanggalSurat = self::parseTanggal($this->cellValue($sheet, 'C', $row));
        if ($tanggalSurat === null) {
            $this->recordSkip($report, self::SHEET_AGENDA, $row, 'masuk', 'tanggal surat tidak dapat diurai');

            return;
        }

        if ((int) $tanggalSurat->format('Y') !== self::IMPORT_YEAR) {
            $this->recordSkip($report, self::SHEET_AGENDA, $row, 'masuk', 'bukan tahun '.self::IMPORT_YEAR);

            return;
        }

        $noSurat = $this->cellString($sheet, 'E', $row);
        $perihal = $this->cellString($sheet, 'G', $row);
        if ($noSurat === '' || $perihal === '') {
            $this->recordSkip($report, self::SHEET_AGENDA, $row, 'masuk', 'no surat atau perihal kosong');

            return;
        }

        $tanggalTerima = self::parseTanggal($this->cellValue($sheet, 'D', $row));
        $diteruskan = $this->cellString($sheet, 'I', $row);
        $catatan = $diteruskan !== '' ? 'Diteruskan kepada: '.$diteruskan : null;

        $report->addPreview([
            'sheet' => self::SHEET_AGENDA,
            'baris' => (string) $row,
            'jenis' => AgendaSurat::JENIS_MASUK,
            'no_surat' => $noSurat,
            'tanggal' => $tanggalSurat->toDateString(),
        ]);

        $this->persistAgendaSurat(
            $kelurahan,
            $report,
            self::SHEET_AGENDA,
            $row,
            'masuk',
            [
                'jenis' => AgendaSurat::JENIS_MASUK,
                'tanggal_surat' => $tanggalSurat,
                'tanggal_terima' => $tanggalTerima,
                'no_surat' => $noSurat,
                'dari' => $this->nullableString($this->cellString($sheet, 'F', $row)),
                'perihal' => $perihal,
                'lampiran' => $this->nullableString($this->cellString($sheet, 'H', $row)),
                'catatan' => $catatan,
            ],
        );
    }

    private function processAgendaKeluar(
        Worksheet $sheet,
        Kelurahan $kelurahan,
        Buku2026ImportReport $report,
        int $row,
    ): void {
        $noSurat = $this->cellString($sheet, 'K', $row);
        if ($noSurat === '') {
            return;
        }

        $report->read++;

        $tanggalSurat = self::parseTanggal($this->cellValue($sheet, 'L', $row));
        if ($tanggalSurat === null) {
            $this->recordSkip($report, self::SHEET_AGENDA, $row, 'keluar', 'tanggal surat tidak dapat diurai');

            return;
        }

        if ((int) $tanggalSurat->format('Y') !== self::IMPORT_YEAR) {
            $this->recordSkip($report, self::SHEET_AGENDA, $row, 'keluar', 'bukan tahun '.self::IMPORT_YEAR);

            return;
        }

        $perihal = $this->cellString($sheet, 'N', $row);
        if ($perihal === '') {
            $this->recordSkip($report, self::SHEET_AGENDA, $row, 'keluar', 'perihal kosong');

            return;
        }

        $report->addPreview([
            'sheet' => self::SHEET_AGENDA,
            'baris' => (string) $row,
            'jenis' => AgendaSurat::JENIS_KELUAR,
            'no_surat' => $noSurat,
            'tanggal' => $tanggalSurat->toDateString(),
        ]);

        $this->persistAgendaSurat(
            $kelurahan,
            $report,
            self::SHEET_AGENDA,
            $row,
            'keluar',
            [
                'jenis' => AgendaSurat::JENIS_KELUAR,
                'tanggal_surat' => $tanggalSurat,
                'tanggal_terima' => null,
                'no_surat' => $noSurat,
                'kepada' => $this->nullableString($this->cellString($sheet, 'M', $row)),
                'perihal' => $perihal,
                'lampiran' => $this->nullableString($this->cellString($sheet, 'O', $row)),
                'tembusan' => $this->nullableString($this->cellString($sheet, 'P', $row)),
                'catatan' => null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persistAgendaSurat(
        Kelurahan $kelurahan,
        Buku2026ImportReport $report,
        string $sheetName,
        int $row,
        string $side,
        array $data,
    ): void {
        $existing = AgendaSurat::query()
            ->where('kelurahan_id', $kelurahan->id)
            ->where('jenis', $data['jenis'])
            ->where('no_surat', $data['no_surat'])
            ->whereDate('tanggal_surat', $data['tanggal_surat']->toDateString())
            ->first();

        if ($this->dryRun) {
            if ($existing === null) {
                $report->created++;
            } else {
                $report->updated++;
            }

            return;
        }

        try {
            DB::transaction(function () use ($kelurahan, $report, $sheetName, $row, $side, $data, $existing): void {
                if ($existing !== null) {
                    $existing->fill([
                        'tanggal_terima' => $data['tanggal_terima'] ?? $existing->tanggal_terima,
                        'dari' => $data['dari'] ?? $existing->dari,
                        'kepada' => $data['kepada'] ?? $existing->kepada,
                        'perihal' => $data['perihal'],
                        'lampiran' => $data['lampiran'] ?? $existing->lampiran,
                        'tembusan' => $data['tembusan'] ?? $existing->tembusan,
                        'catatan' => $data['catatan'] ?? $existing->catatan,
                    ]);
                    if ($existing->isDirty()) {
                        $existing->save();
                        $report->updated++;
                    } else {
                        $report->skipped++;
                        $this->recordSkip($report, $sheetName, $row, $side, 'sudah ada tanpa perubahan');
                    }

                    return;
                }

                $tahun = (int) $data['tanggal_surat']->format('Y');
                $noUrut = AgendaSurat::nomorUrutBerikutnya(
                    $kelurahan->id,
                    $data['jenis'],
                    null,
                    $tahun,
                );

                AgendaSurat::query()->create([
                    'kelurahan_id' => $kelurahan->id,
                    'jenis' => $data['jenis'],
                    'pokja_id' => null,
                    'no_urut_tahun' => $noUrut,
                    'tahun' => $tahun,
                    'tanggal_surat' => $data['tanggal_surat'],
                    'tanggal_terima' => $data['tanggal_terima'],
                    'no_surat' => $data['no_surat'],
                    'dari' => $data['dari'] ?? null,
                    'kepada' => $data['kepada'] ?? null,
                    'perihal' => $data['perihal'],
                    'lampiran' => $data['lampiran'] ?? null,
                    'tembusan' => $data['tembusan'] ?? null,
                    'catatan' => $data['catatan'] ?? null,
                ]);

                $report->created++;
            });
        } catch (Throwable $e) {
            $report->failed++;
            $report->failedRows[] = [
                'sheet' => $sheetName,
                'row' => $row,
                'side' => $side,
                'reason' => mb_substr($e->getMessage(), 0, 200),
            ];
        }
    }

    private function findOrang(int $kelurahanId, string $nama, ?Carbon $tanggalLahir, ?string $alamat): ?Orang
    {
        $candidates = Orang::query()
            ->where('kelurahan_id', $kelurahanId)
            ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim($nama))])
            ->get();

        foreach ($candidates as $orang) {
            if ($tanggalLahir !== null) {
                if ($orang->tanggal_lahir !== null
                    && $orang->tanggal_lahir->toDateString() === $tanggalLahir->toDateString()) {
                    return $orang;
                }

                continue;
            }

            if ($alamat !== null && $alamat !== ''
                && $orang->alamat !== null
                && mb_strtolower(trim($orang->alamat)) === mb_strtolower(trim($alamat))) {
                return $orang;
            }
        }

        if ($tanggalLahir === null && ($alamat === null || $alamat === '') && $candidates->count() === 1) {
            return $candidates->first();
        }

        return null;
    }

    private function findKeanggotaan(int $orangId, string $jenis, string $jabatan): ?Keanggotaan
    {
        $jabatanKey = mb_strtolower(trim($jabatan));

        return Keanggotaan::query()
            ->where('orang_id', $orangId)
            ->where('jenis', $jenis)
            ->get()
            ->first(fn (Keanggotaan $k) => mb_strtolower(trim((string) $k->jabatan)) === $jabatanKey);
    }

    private function jenisDariPenanda(string $markerUmum, string $markerKhusus): string
    {
        if ($this->isPenandaAktif($markerUmum)) {
            return Keanggotaan::JENIS_KADER_UMUM;
        }

        if ($this->isPenandaAktif($markerKhusus)) {
            return Keanggotaan::JENIS_KADER_KHUSUS;
        }

        return Keanggotaan::JENIS_TP_PKK;
    }

    private function isPenandaAktif(string $value): bool
    {
        $v = mb_strtolower(trim($value));

        if ($v === '') {
            return false;
        }

        return in_array($v, ['v', 'x', '√', '1', 'ya', 'y', '*', '✓'], true)
            || str_contains($v, 'kader');
    }

    private function findRowAfterColumnNumbers(Worksheet $sheet, string $namaHeader): int
    {
        $maxRow = (int) $sheet->getHighestRow();
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($row = 1; $row <= $maxRow; $row++) {
            for ($col = 1; $col <= $maxCol; $col++) {
                $coord = Coordinate::stringFromColumnIndex($col).$row;
                $value = $sheet->getCell($coord)->getCalculatedValue();
                if (is_string($value) && stripos($value, $namaHeader) !== false) {
                    return $row + 2;
                }
            }
        }

        return 12;
    }

    private function shouldSkipNama(string $nama): bool
    {
        $nama = trim($nama);
        if ($nama === '') {
            return true;
        }

        if (mb_strtoupper($nama) === 'FOTO') {
            return true;
        }

        if (preg_match('/^\d+$/', $nama)) {
            return true;
        }

        return false;
    }

    private function normalizeJenisKelamin(string $value): ?string
    {
        $v = mb_strtoupper(trim($value));
        if ($v === 'L' || $v === 'P') {
            return $v;
        }

        return null;
    }

    private function isDataNumber(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_numeric($value)) {
            $int = (int) $value;

            return $int >= 1 && $int <= 9999;
        }

        return ctype_digit(trim((string) $value));
    }

    private function cellValue(Worksheet $sheet, string $col, int $row): mixed
    {
        return $sheet->getCell($col.$row)->getCalculatedValue();
    }

    private function cellString(Worksheet $sheet, string $col, int $row): string
    {
        $value = $this->cellValue($sheet, $col, $row);

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function nullableString(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function recordSkip(
        Buku2026ImportReport $report,
        string $sheet,
        int $row,
        string $side,
        string $reason,
    ): void {
        $report->skipped++;
        $report->skippedRows[] = [
            'sheet' => $sheet,
            'row' => $row,
            'side' => $side,
            'reason' => $reason,
        ];
    }
}
