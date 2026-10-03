<?php

namespace App\Console\Commands;

use App\Services\Buku2026Importer;
use App\Services\Buku2026ImportReport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('pkk:import-buku2026
    {--file=docs/source/Buku PKK GSI new.xlsx : Path berkas Excel sumber}
    {--execute : Aktifkan mode eksekusi (wajib bersama --force untuk menulis)}
    {--force : Izinkan penulisan ke database}')]
#[Description('Import data awal tahun 2026 dari buku administrasi PKK (Excel)')]
class ImportBuku2026 extends Command
{
    public function handle(): int
    {
        $file = (string) $this->option('file');
        $execute = (bool) $this->option('execute');
        $force = (bool) $this->option('force');

        if ($execute && ! $force) {
            $this->error('Penulisan ke database memerlukan --execute dan --force bersamaan.');

            return self::FAILURE;
        }

        $path = $this->resolvePath($file);
        if (! is_readable($path)) {
            $this->error("Berkas tidak ditemukan atau tidak dapat dibaca: {$path}");

            return self::FAILURE;
        }

        $dryRun = ! ($execute && $force);

        if ($dryRun) {
            $this->info('Mode dry-run (default): tidak ada penulisan ke database.');
        } else {
            $this->warn('Mode eksekusi: data akan ditulis ke database.');
        }

        try {
            $importer = new Buku2026Importer($dryRun);
            $report = $importer->run($path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->printSummary($report, $dryRun);
        $this->printPreviewTable($report);

        $logPath = storage_path('logs/import-buku2026-'.now()->format('Y-m-d_His').'.log');
        File::ensureDirectoryExists(dirname($logPath));
        $report->writeLog($logPath);
        $this->line("Laporan lengkap: {$logPath}");

        return self::SUCCESS;
    }

    private function resolvePath(string $file): string
    {
        if ($file !== '' && $file[0] === '/') {
            return $file;
        }

        return base_path($file);
    }

    private function printSummary(Buku2026ImportReport $report, bool $dryRun): void
    {
        $totals = $report->totals();
        $this->newLine();
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Baris dibaca', (string) $totals['read']],
                [$dryRun ? 'Akan dibuat' : 'Dibuat', (string) $totals['created']],
                [$dryRun ? 'Akan diperbarui' : 'Diperbarui', (string) $totals['updated']],
                ['Dilewati', (string) $totals['skipped']],
                ['Gagal', (string) $totals['failed']],
                ['Digabung (tanpa tanggal lahir)', (string) $report->mergedRows],
            ],
        );

        if ($report->mergedNames !== []) {
            $this->info('Baris digabung ke orang yang sudah ada:');
            foreach ($report->mergedNames as $nama) {
                $this->line("  - {$nama}");
            }
        }

        if ($report->needsReviewCases !== []) {
            $this->warn('Perlu diperiksa manusia (nama sama, tanggal lahir berbeda):');
            foreach ($report->needsReviewCases as $case) {
                $this->line("  - {$case['nama']}: {$case['detail']}");
            }
        }

        if ($report->failedRows !== []) {
            $this->warn('Baris gagal:');
            foreach (array_slice($report->failedRows, 0, 15) as $item) {
                $this->line("  [{$item['sheet']}] baris {$item['row']} ({$item['side']}): {$item['reason']}");
            }
        }
    }

    private function printPreviewTable(Buku2026ImportReport $report): void
    {
        if ($report->previewRows === []) {
            return;
        }

        $this->newLine();
        $this->info('Cuplikan baris (maks. 25):');
        $headers = array_keys($report->previewRows[0]);
        $rows = array_map(
            fn (array $row) => array_map(fn ($key) => $row[$key] ?? '', $headers),
            $report->previewRows,
        );
        $this->table($headers, $rows);
    }
}
