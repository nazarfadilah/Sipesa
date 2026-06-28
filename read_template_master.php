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
    die("Rekap Neraca Pengelolaan Sampah sheet not found in template_master\n");
}
echo "Sheet: " . $sheet->getTitle() . "\n";
for ($row = 8; $row <= 18; $row++) {
    $rowStr = "";
    for ($colChar = ord('C'); $colChar <= ord('M'); $colChar++) {
        $col = chr($colChar);
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val !== null && $val !== '') {
            echo "$col$row: $val | ";
        }
    }
    echo "\n";
}
echo "\n";
