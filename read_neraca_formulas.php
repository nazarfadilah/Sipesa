<?php
ini_set('memory_limit', '1024M');
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__.'/Laporan_Neraca_2025.xlsx';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('Rekap Neraca Pengelolaan Sampah');
if (!$sheet) {
    die("Rekap Neraca Pengelolaan Sampah sheet not found\n");
}
echo "Sheet title: " . $sheet->getTitle() . "\n";
echo "Row 9 to 18 data:\n";
for ($row = 9; $row <= 18; $row++) {
    $rowStr = "";
    for ($colChar = ord('C'); $colChar <= ord('M'); $colChar++) {
        $col = chr($colChar);
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val !== null && $val !== '') {
            $calc = $sheet->getCell($col . $row)->getCalculatedValue();
            $rowStr .= "$col$row: $val (calc: $calc) | ";
        }
    }
    if ($rowStr !== "") {
        echo "Row $row: $rowStr\n";
    }
}
