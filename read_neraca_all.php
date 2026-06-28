<?php
ini_set('memory_limit', '1024M');
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__.'/storage/app/public/templates/template_master.xlsx';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('Rekap Neraca Pengelolaan Sampah');
if (!$sheet) {
    die("Rekap Neraca Pengelolaan Sampah sheet not found\n");
}
echo "Sheet title: " . $sheet->getTitle() . "\n";
for ($row = 7; $row <= 16; $row++) {
    $rowStr = "";
    $loc = $sheet->getCell('C' . $row)->getValue();
    for ($colChar = ord('D'); $colChar <= ord('L'); $colChar++) {
        $col = chr($colChar);
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val !== null && $val !== '') {
            $rowStr .= "$col$row: $val | ";
        }
    }
    echo "Row $row ($loc): $rowStr\n";
}
