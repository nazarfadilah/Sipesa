<?php
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = __DIR__ . '/storage/app/public/templates/template_master.xlsx';
try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getSheetByName('Rekap area');
    if (!$sheet) {
        echo "Sheet 'Rekap area' not found\n";
    } else {
        echo "Sheet Rekap area cells (rows 5 to 7):\n";
        for ($row = 5; $row <= 7; $row++) {
            $rowStr = "";
            for ($col = 'A'; $col <= 'Z'; $col++) {
                $val = $sheet->getCell($col . $row)->getValue();
                if ($val !== null && $val !== '') {
                    $rowStr .= "$col$row: $val | ";
                }
            }
            if ($rowStr) {
                echo "$rowStr\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
