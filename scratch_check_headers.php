<?php
ini_set('memory_limit', '1024M');
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

ob_start();

$file = __DIR__ . '/test_output.xlsx';
if (!file_exists($file)) {
    echo "File not found: $file\n";
    exit;
}
try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getSheetByName('Rekap Neraca Pengelolaan Sampah');
    if (!$sheet) {
        echo "Sheet 'Rekap Neraca Pengelolaan Sampah' not found in test output\n";
    } else {
        echo "Sheet 'Rekap Neraca Pengelolaan Sampah' cells (rows 1-15):\n";
        for ($row = 1; $row <= 15; $row++) {
            $rowStr = "";
            for ($colChar = ord('A'); $colChar <= ord('Z'); $colChar++) {
                $col = chr($colChar);
                $val = $sheet->getCell($col . $row)->getValue();
                if ($val !== null && $val !== '') {
                    $rowStr .= "$col$row: $val | ";
                }
            }
            if ($rowStr) {
                echo "Row $row: $rowStr\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
file_put_contents(__DIR__ . '/scratch_out.txt', ob_get_clean());
