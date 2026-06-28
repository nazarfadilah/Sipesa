<?php
ini_set('memory_limit', '1024M');
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__.'/storage/app/public/templates/template_master.xlsx';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}
$spreadsheet = IOFactory::load($filePath);

// 1. Fix "Rekap Neraca Pengelolaan Sampah"
$sheetNeraca = $spreadsheet->getSheetByName('Rekap Neraca Pengelolaan Sampah');
if (!$sheetNeraca) {
    die("Rekap Neraca sheet not found\n");
}

// Kantor (Row 9)
$sheetNeraca->setCellValue('E9', "='Rekap area'!D20");
$sheetNeraca->setCellValue('F9', "='Rekap area'!E20");
$sheetNeraca->setCellValue('I9', "='Rekap area'!F20");

// Parkir / Taman (Row 10)
$sheetNeraca->setCellValue('E10', "='Rekap area'!H20");
$sheetNeraca->setCellValue('F10', "='Rekap area'!I20");
$sheetNeraca->setCellValue('I10', "='Rekap area'!J20");

// Ruang Tunggu (Row 11)
$sheetNeraca->setCellValue('E11', "='Rekap area'!L20");
$sheetNeraca->setCellValue('F11', "='Rekap area'!M20");
$sheetNeraca->setCellValue('I11', "='Rekap area'!N20");

// Kantin / Pujasera (Row 12)
$sheetNeraca->setCellValue('E12', "='Rekap area'!P20");
$sheetNeraca->setCellValue('F12', "='Rekap area'!Q20");
$sheetNeraca->setCellValue('I12', "='Rekap area'!R20");

// Kapal / Dermaga (Row 13)
$sheetNeraca->setCellValue('E13', "='Rekap area'!T20");
$sheetNeraca->setCellValue('F13', "='Rekap area'!U20");
$sheetNeraca->setCellValue('I13', "='Rekap area'!V20");

// Area Lainnya (Row 14)
$sheetNeraca->setCellValue('E14', "='Rekap area'!X20");
$sheetNeraca->setCellValue('F14', "='Rekap area'!Y20");
$sheetNeraca->setCellValue('I14', "='Rekap area'!Z20");

echo "Fixed Rekap Neraca formulas.\n";

// 2. Fix "Rekap area" rows for Juli (8), Agustus (9), September (10)
$sheetArea = $spreadsheet->getSheetByName('Rekap area');
if (!$sheetArea) {
    die("Rekap area sheet not found\n");
}

$monthsToFix = [
    8 => 'Juli',
    9 => 'Agustus',
    10 => 'September'
];

foreach ($monthsToFix as $row => $monthName) {
    $sheetArea->setCellValue('C' . $row, "='Rekap Neraca Pengelolaan Sampah'!\$D\$3"); // wait! Let's check: C11 is =$D$3. Ah, in Rekap area, D3 contains the start year. So pointing to =$D$3 is correct.
    // Kantor
    $sheetArea->setCellValue('D' . $row, "='{$monthName}'!\$C\$40");
    $sheetArea->setCellValue('E' . $row, "='{$monthName}'!\$D\$40");
    $sheetArea->setCellValue('F' . $row, "='{$monthName}'!\$E\$40");
    $sheetArea->setCellValue('G' . $row, "=SUM(D{$row}:F{$row})");
    // Parkir
    $sheetArea->setCellValue('H' . $row, "='{$monthName}'!G\$40");
    $sheetArea->setCellValue('I' . $row, "='{$monthName}'!H\$40");
    $sheetArea->setCellValue('J' . $row, "='{$monthName}'!I\$40");
    $sheetArea->setCellValue('K' . $row, "=SUM(H{$row}:J{$row})");
    // Ruang Tunggu
    $sheetArea->setCellValue('L' . $row, "='{$monthName}'!K\$40");
    $sheetArea->setCellValue('M' . $row, "='{$monthName}'!L\$40");
    $sheetArea->setCellValue('N' . $row, "='{$monthName}'!M\$40");
    $sheetArea->setCellValue('O' . $row, "=SUM(L{$row}:N{$row})");
    // Tempat Makan
    $sheetArea->setCellValue('P' . $row, "='{$monthName}'!O\$40");
    $sheetArea->setCellValue('Q' . $row, "='{$monthName}'!P\$40");
    $sheetArea->setCellValue('R' . $row, "='{$monthName}'!Q\$40");
    $sheetArea->setCellValue('S' . $row, "=SUM(P{$row}:R{$row})");
    // Kapal
    $sheetArea->setCellValue('T' . $row, "='{$monthName}'!S\$40");
    $sheetArea->setCellValue('U' . $row, "='{$monthName}'!T\$40");
    $sheetArea->setCellValue('V' . $row, "='{$monthName}'!U\$40");
    $sheetArea->setCellValue('W' . $row, "=SUM(T{$row}:V{$row})");
    // Area Lain
    $sheetArea->setCellValue('X' . $row, "='{$monthName}'!W\$40");
    $sheetArea->setCellValue('Y' . $row, "='{$monthName}'!X\$40");
    $sheetArea->setCellValue('Z' . $row, "='{$monthName}'!Y\$40");
    $sheetArea->setCellValue('AA' . $row, "=SUM(X{$row}:Z{$row})");
}

echo "Fixed Rekap area formulas for July, August, September.\n";

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save($filePath);
echo "Saved template successfully!\n";
