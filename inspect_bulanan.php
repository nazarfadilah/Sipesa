<?php
ini_set('memory_limit', '1024M');
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = __DIR__.'/Laporan_Bulanan_Admin_2025 (2).xlsx';
echo "Checking: $filePath\n\n";

if (!file_exists($filePath)) {
    die("File not found!\n");
}

$spreadsheet = IOFactory::load($filePath);
$sheetNames = $spreadsheet->getSheetNames();
echo "Total Sheets: " . count($sheetNames) . "\n";
echo "Sheet Names: " . implode(', ', $sheetNames) . "\n\n";

foreach ($spreadsheet->getSheetNames() as $sheetName) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "=== Sheet: $sheetName ===\n";
    echo str_repeat("=", 60) . "\n";
    
    $maxRow = $sheet->getHighestRow();
    $maxCol = $sheet->getHighestColumn();
    echo "Dimensions: A1 to $maxCol$maxRow\n\n";
    
    // Print first 15 rows to understand structure
    echo "--- HEADER ROWS (1-15) ---\n";
    for ($row = 1; $row <= min(15, $maxRow); $row++) {
        $rowStr = "";
        for ($colChar = ord('A'); $colChar <= ord('Z'); $colChar++) {
            $col = chr($colChar);
            $val = $sheet->getCell($col . $row)->getValue();
            if ($val !== null && $val !== '') {
                $rowStr .= "$col$row: $val | ";
            }
        }
        if ($rowStr) echo "Row $row: $rowStr\n";
    }
    
    // Print data rows (look for non-empty cells in data area)
    echo "\n--- DATA ROWS (looking for non-zero values) ---\n";
    $hasData = false;
    for ($row = 8; $row <= $maxRow; $row++) {
        $rowStr = "";
        for ($colChar = ord('C'); $colChar <= ord('Z'); $colChar++) {
            $col = chr($colChar);
            $val = $sheet->getCell($col . $row)->getValue();
            if ($val !== null && $val !== '' && $val != 0) {
                $rowStr .= "$col$row: $val | ";
            }
        }
        if ($rowStr) {
            $dateVal = $sheet->getCell('B' . $row)->getValue();
            echo "Row $row (B=$dateVal): $rowStr\n";
            $hasData = true;
        }
    }
    if (!$hasData) echo "No non-zero data found in rows 8+\n";
    
    // Show the last few rows with any content
    echo "\n--- LAST ROWS (any content) ---\n";
    for ($row = max(1, $maxRow - 10); $row <= $maxRow; $row++) {
        $rowStr = "";
        for ($colChar = ord('A'); $colChar <= ord('Z'); $colChar++) {
            $col = chr($colChar);
            $val = $sheet->getCell($col . $row)->getValue();
            if ($val !== null && $val !== '') {
                $rowStr .= "$col$row: $val | ";
            }
        }
        if ($rowStr) echo "Row $row: $rowStr\n";
    }
}

echo "\n\nDone.\n";
