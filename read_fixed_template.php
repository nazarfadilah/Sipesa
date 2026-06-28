<?php
ini_set('memory_limit', '1024M');
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__.'/storage/app/public/templates/template_master.xlsx';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}
$spreadsheet = IOFactory::load($filePath);

// Check Rekap Neraca
$sheetNeraca = $spreadsheet->getSheetByName('Rekap Neraca Pengelolaan Sampah');
echo "=== Rekap Neraca Formulas ===\n";
for ($row = 9; $row <= 14; $row++) {
    $loc = $sheetNeraca->getCell('C' . $row)->getValue();
    $e = $sheetNeraca->getCell('E' . $row)->getValue();
    $f = $sheetNeraca->getCell('F' . $row)->getValue();
    $i = $sheetNeraca->getCell('I' . $row)->getValue();
    echo "Row $row ($loc): E: $e | F: $f | I: $i\n";
}

// Check Rekap area
$sheetArea = $spreadsheet->getSheetByName('Rekap area');
echo "\n=== Rekap area Formulas ===\n";
for ($row = 8; $row <= 12; $row++) {
    $month = $sheetArea->getCell('B' . $row)->getValue();
    $c = $sheetArea->getCell('C' . $row)->getValue();
    $d = $sheetArea->getCell('D' . $row)->getValue();
    $e = $sheetArea->getCell('E' . $row)->getValue();
    $f = $sheetArea->getCell('F' . $row)->getValue();
    $g = $sheetArea->getCell('G' . $row)->getValue();
    echo "Row $row ($month): C: $c | D: $d | E: $e | F: $f | G: $g\n";
}
