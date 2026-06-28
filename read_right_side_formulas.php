<?php
ini_set('memory_limit', '1024M');
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__.'/Laporan_Neraca_2025.xlsx';
if (!file_exists($filePath)) {
    die("File not found: $filePath\n");
}
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getSheetByName('Juli');
if (!$sheet) {
    die("Juli sheet not found\n");
}
echo "Row 8 to 12 right side formulas:\n";
for ($row = 8; $row <= 12; $row++) {
    $rowStr = "";
    for ($colChar = ord('A'); $colChar <= ord('Q'); $colChar++) {
        // We want AD to AQ, which are columns 30 to 43. Let's write them as strings:
        // AD, AE, AF, AG, AH, AI, AJ, AK, AL, AM, AN, AO, AP, AQ
    }
    $cols = ['AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ'];
    foreach ($cols as $col) {
        $val = $sheet->getCell($col . $row)->getValue();
        if ($val !== null && $val !== '') {
            $calc = $sheet->getCell($col . $row)->getCalculatedValue();
            $rowStr .= "$col$row: $val (calc: $calc) | ";
        }
    }
    echo "Row $row: $rowStr\n";
}
