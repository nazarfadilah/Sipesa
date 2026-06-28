<?php
ini_set('memory_limit', '1024M');
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__.'/Laporan_Neraca_2025.xlsx';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('Rekap area');
if (!$sheet) {
    die("Rekap area sheet not found\n");
}
echo "Sheet title: " . $sheet->getTitle() . "\n";
echo "Row 4:\n";
for ($colChar = ord('A'); $colChar <= ord('Z'); $colChar++) {
    $col = chr($colChar);
    $val = $sheet->getCell($col . '4')->getValue();
    if ($val !== null && $val !== '') echo "$col: $val | ";
}
echo "\nRow 5:\n";
for ($colChar = ord('A'); $colChar <= ord('Z'); $colChar++) {
    $col = chr($colChar);
    $val = $sheet->getCell($col . '5')->getValue();
    if ($val !== null && $val !== '') echo "$col: $val | ";
}
echo "\nRow 6:\n";
for ($colChar = ord('A'); $colChar <= ord('Z'); $colChar++) {
    $col = chr($colChar);
    $val = $sheet->getCell($col . '6')->getValue();
    if ($val !== null && $val !== '') echo "$col: $val | ";
}
echo "\nRow 20:\n";
for ($colChar = ord('A'); $colChar <= ord('Z'); $colChar++) {
    $col = chr($colChar);
    $val = $sheet->getCell($col . '20')->getValue();
    if ($val !== null && $val !== '') {
        $calc = $sheet->getCell($col . '20')->getCalculatedValue();
        echo "$col: $val (calc: $calc) | ";
    }
}
echo "\n";
