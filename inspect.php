<?php
ini_set('memory_limit', '2048M');
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "=== INSPECTING TEMPLATE MASTER ===\n";
$path1 = __DIR__.'/storage/app/public/templates/template_master.xlsx';
if (file_exists($path1)) {
    $reader1 = IOFactory::createReaderForFile($path1);
    $reader1->setReadDataOnly(true);
    $spreadsheet = $reader1->load($path1);
    echo "Sheet names in template_master:\n";
    print_r($spreadsheet->getSheetNames());
    
    // Let's check sheet "Juni"
    $sheet = $spreadsheet->getSheetByName('Juni');
    if ($sheet) {
        echo "\nSheet 'Juni':\n";
        echo "Highest Row: " . $sheet->getHighestRow() . "\n";
        echo "Highest Column: " . $sheet->getHighestColumn() . "\n";
        
        // Let's print rows 6 to 12
        for ($row = 6; $row <= 12; $row++) {
            $rowValues = [];
            for ($col = 'A'; $col <= 'K'; $col++) {
                $rowValues[$col] = $sheet->getCell($col.$row)->getValue();
            }
            echo "Row $row: " . json_encode($rowValues) . "\n";
        }
    } else {
        echo "Sheet 'Juni' not found\n";
    }
} else {
    echo "template_master.xlsx not found\n";
}

echo "\n=== INSPECTING TEMPLATE BULANAN MASTER ===\n";
$path2 = __DIR__.'/storage/app/public/templates/template_bulanan_master.xlsx';
if (file_exists($path2)) {
    $reader2 = IOFactory::createReaderForFile($path2);
    $reader2->setReadDataOnly(true);
    $spreadsheet = $reader2->load($path2);
    echo "Sheet names in template_bulanan_master:\n";
    print_r($spreadsheet->getSheetNames());
    
    $sheet = $spreadsheet->getSheetByName('Rekap Bulanan');
    if ($sheet) {
        echo "\nSheet 'Rekap Bulanan':\n";
        echo "Highest Row: " . $sheet->getHighestRow() . "\n";
        echo "Highest Column: " . $sheet->getHighestColumn() . "\n";
        
        // Let's print rows 6 to 12
        for ($row = 6; $row <= 12; $row++) {
            $rowValues = [];
            for ($col = 'A'; $col <= 'K'; $col++) {
                $rowValues[$col] = $sheet->getCell($col.$row)->getValue();
            }
            echo "Row $row: " . json_encode($rowValues) . "\n";
        }
    } else {
        echo "Sheet 'Rekap Bulanan' not found\n";
    }
} else {
    echo "template_bulanan_master.xlsx not found\n";
}
