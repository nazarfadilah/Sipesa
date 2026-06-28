<?php
ini_set('memory_limit', '1024M');
require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

echo "=== STARTING PURE EXPORT TEST ===\n";

$tahunInput = 2025;

$templatePath = __DIR__ . '/storage/app/public/templates/template_master.xlsx';
if (!file_exists($templatePath)) {
    die("File template_master.xlsx tidak ditemukan!\n");
}

echo "Loading template...\n";
$spreadsheet = IOFactory::load($templatePath);

$sheetNeraca = $spreadsheet->getSheetByName('Rekap Neraca Pengelolaan Sampah');
if ($sheetNeraca) {
    echo "Updating formulas on sheet...\n";
    $sheetNeraca->setCellValue('D2', $tahunInput - 1); // Tahun Awal
    $sheetNeraca->setCellValue('G2', $tahunInput);     // Tahun Akhir
    $sheetNeraca->setCellValue('C4', "SELURUH WILAYAH OPERASIONAL");   // Nama Instansi
    
    // Fix template bug:
    $sheetNeraca->setCellValue('E9', "='Rekap area'!\$L\$20");
    $sheetNeraca->setCellValue('F9', "='Rekap area'!\$M\$20");
    $sheetNeraca->setCellValue('I9', "='Rekap area'!\$N\$20");
    
    $sheetNeraca->setCellValue('E10', "='Rekap area'!\$P\$20");
    $sheetNeraca->setCellValue('F10', "='Rekap area'!\$Q\$20");
    $sheetNeraca->setCellValue('I10', "='Rekap area'!\$R\$20");
    
    $sheetNeraca->setCellValue('E11', "='Rekap area'!\$T\$20");
    $sheetNeraca->setCellValue('F11', "='Rekap area'!\$U\$20");
    $sheetNeraca->setCellValue('I11', "='Rekap area'!\$V\$20");
    
    $sheetNeraca->setCellValue('E12', "='Rekap area'!\$X\$20");
    $sheetNeraca->setCellValue('F12', "='Rekap area'!\$Y\$20");
    $sheetNeraca->setCellValue('I12', "='Rekap area'!\$Z\$20");

    // Memperbaiki Baris Total (Row 13) agar menjumlahkan baris 7 s.d 12
    $sheetNeraca->setCellValue('E13', "=SUM(E7:E12)");
    $sheetNeraca->setCellValue('F13', "=SUM(F7:F12)");
    $sheetNeraca->setCellValue('I13', "=SUM(I7:I12)");

    // Mengosongkan Baris 14 (sisa formula salah bawaan template)
    $sheetNeraca->setCellValue('E14', null);
    $sheetNeraca->setCellValue('F14', null);
    $sheetNeraca->setCellValue('I14', null);
} else {
    die("Sheet Rekap Neraca tidak ditemukan!\n");
}

$outputPath = __DIR__ . '/test_output_pure.xlsx';
echo "Saving test_output_pure.xlsx...\n";
$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);
echo "File saved.\n\n";

// --- VERIFIKASI ---
echo "=== VERIFYING SAVED FILE ===\n";
$spreadsheetTest = IOFactory::load($outputPath);
$sheetTest = $spreadsheetTest->getSheetByName('Rekap Neraca Pengelolaan Sampah');

for ($row = 7; $row <= 15; $row++) {
    $loc = $sheetTest->getCell('B' . $row)->getValue();
    $e = $sheetTest->getCell('E' . $row)->getValue();
    $f = $sheetTest->getCell('F' . $row)->getValue();
    $i = $sheetTest->getCell('I' . $row)->getValue();
    echo "Row $row ($loc): E: $e | F: $f | I: $i\n";
}

echo "\nDone.\n";
