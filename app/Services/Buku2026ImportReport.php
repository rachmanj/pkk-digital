<?php

namespace App\Services;

class Buku2026ImportReport
{
    public int $read = 0;

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $failed = 0;

    public int $mergedRows = 0;

    /** @var list<string> */
    public array $mergedNames = [];

    /** @var list<array{nama: string, detail: string}> */
    public array $needsReviewCases = [];

    /** @var list<array{sheet: string, row: int, side: string, reason: string}> */
    public array $skippedRows = [];

    /** @var list<array{sheet: string, row: int, side: string, reason: string}> */
    public array $failedRows = [];

    /** @var list<array<string, string>> */
    public array $previewRows = [];

    public function addPreview(array $row): void
    {
        if (count($this->previewRows) < 25) {
            $this->previewRows[] = $row;
        }
    }

    /**
     * @return array{read: int, created: int, updated: int, skipped: int, failed: int}
     */
    public function totals(): array
    {
        return [
            'read' => $this->read,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
        ];
    }

    public function writeLog(string $path): void
    {
        $lines = [
            'Import Buku PKK 2026 — '.now()->toDateTimeString(),
            '',
            'Ringkasan:',
            "  dibaca: {$this->read}",
            "  dibuat: {$this->created}",
            "  diperbarui: {$this->updated}",
            "  dilewati: {$this->skipped}",
            "  gagal: {$this->failed}",
            "  digabung (salah satu baris tanpa tanggal lahir): {$this->mergedRows}",
            '',
        ];

        if ($this->mergedNames !== []) {
            $lines[] = 'Nama digabung ke baris orang yang sudah ada:';
            foreach ($this->mergedNames as $nama) {
                $lines[] = "  - {$nama}";
            }
            $lines[] = '';
        }

        if ($this->needsReviewCases !== []) {
            $lines[] = 'Perlu diperiksa manusia (nama sama, tanggal lahir keduanya ada dan berbeda):';
            foreach ($this->needsReviewCases as $case) {
                $lines[] = "  - {$case['nama']}: {$case['detail']}";
            }
            $lines[] = '';
        }

        if ($this->skippedRows !== []) {
            $lines[] = 'Baris dilewati:';
            foreach ($this->skippedRows as $item) {
                $lines[] = "  [{$item['sheet']}] baris {$item['row']} ({$item['side']}): {$item['reason']}";
            }
            $lines[] = '';
        }

        if ($this->failedRows !== []) {
            $lines[] = 'Baris gagal:';
            foreach ($this->failedRows as $item) {
                $lines[] = "  [{$item['sheet']}] baris {$item['row']} ({$item['side']}): {$item['reason']}";
            }
            $lines[] = '';
        }

        file_put_contents($path, implode(PHP_EOL, $lines));
    }
}
