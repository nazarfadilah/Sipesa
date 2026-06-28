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
for ($row = 8; $row <= 19; $row++) {
    $rowStr = "";
    $month = $sheet->getCell('A' . $row)->getValue();
    for ($colChar = ord('A'); $colChar <= ord('Z'); $colChar++) {
        $col = chr($colChar);
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val !== null && $val !== '' && $val != 0) {
            $calc = $sheet->getCell($col . $row)->getCalculatedValue();
            $rowStr .= "$col$row: $val (calc: $calc) | ";
        }
    }
    if ($rowStr !== "") {
        echo "Row $row ($month): $rowStr\n";
    }
}
