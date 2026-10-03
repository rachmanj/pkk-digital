<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require __DIR__.'/../../vendor/autoload.php';

$spreadsheet = new Spreadsheet;

$anggota = $spreadsheet->getActiveSheet();
$anggota->setTitle('BUKU DAFTAR ANGGOTA (2026)');
$anggota->setCellValue('C9', 'NAMA');
$anggota->setCellValue('D9', 'JABATAN');
$anggota->setCellValue('E9', 'JENIS KELAMIN');
$anggota->setCellValue('G9', 'TGL LAHIR');
$anggota->setCellValue('C12', 'BUDI SANJAYA');
$anggota->setCellValue('D12', 'Ketua');
$anggota->setCellValue('E12', 'L');
$anggota->setCellValue('G12', '1990-05-01');
$anggota->setCellValue('C13', '');
$anggota->setCellValue('D13', 'Anggota');
$anggota->setCellValue('C14', 'DEWI MERGE TEST');
$anggota->setCellValue('D14', 'Sekretaris');
$anggota->setCellValue('E14', 'P');
$anggota->setCellValue('I14', 'Jl. Merge Tetap');

$tp = $spreadsheet->createSheet();
$tp->setTitle('DAFTAR ANGGOTA TP PKK & KADER');
$tp->setCellValue('D9', 'NAMA');
$tp->setCellValue('F9', 'KEDUDUKAN');
$tp->setCellValue('E9', 'JK');
$tp->setCellValue('I9', 'LAHIR');
$tp->setCellValue('D12', 'BUDI SANJAYA');
$tp->setCellValue('E12', 'L');
$tp->setCellValue('F12', 'KETUA');
$tp->setCellValue('I12', '1990-05-01');
$tp->setCellValue('D13', 'SITI KADAR UMUM');
$tp->setCellValue('E13', 'P');
$tp->setCellValue('G13', 'v');
$tp->setCellValue('I13', '1985-01-15');
$tp->setCellValue('D14', 'ANI KADAR KHUSUS');
$tp->setCellValue('E14', 'P');
$tp->setCellValue('H14', '1');
$tp->setCellValue('F14', 'ANGGOTA');
$tp->setCellValue('I14', '1988-03-20');
$tp->setCellValue('D15', 'DEWI MERGE TEST');
$tp->setCellValue('E15', 'P');
$tp->setCellValue('F15', 'SEKRETARIS');
$tp->setCellValue('I15', '06 Februari 1976');
$tp->setCellValue('D16', 'RINA BEDA TGL');
$tp->setCellValue('E16', 'P');
$tp->setCellValue('F16', 'Anggota');
$tp->setCellValue('I16', '1980-01-01');
$tp->setCellValue('D17', 'RINA BEDA TGL');
$tp->setCellValue('E17', 'P');
$tp->setCellValue('F17', 'Bendahara');
$tp->setCellValue('I17', '1990-01-01');

$agenda = $spreadsheet->createSheet();
$agenda->setTitle('BUKU AGENDA SURAT (2)');
$agenda->setCellValue('B9', 1);
$agenda->setCellValue('C9', '2026-01-15');
$agenda->setCellValue('D9', '2026-01-16');
$agenda->setCellValue('E9', 'MASUK/001/2026');
$agenda->setCellValue('F9', 'Dinas');
$agenda->setCellValue('G9', 'Undangan rapat');
$agenda->setCellValue('B11', 2);
$agenda->setCellValue('C11', '28-Juni-26');
$agenda->setCellValue('E11', 'MASUK/002/2026');
$agenda->setCellValue('F11', 'Camat');
$agenda->setCellValue('G11', 'Surat edaran');
$agenda->setCellValue('K11', 'KELUAR/001/2026');
$agenda->setCellValue('L11', '21-Juli-26');
$agenda->setCellValue('M11', 'TP PKK Kota');
$agenda->setCellValue('N11', 'Penugasan');

$target = __DIR__.'/buku2026-mini.xlsx';
(new Xlsx($spreadsheet))->save($target);
echo "Written {$target}\n";
