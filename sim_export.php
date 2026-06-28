<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SampahDiserahkan;
use App\Models\SampahTerkelola;
use Carbon\Carbon;

function getExcelColumn($lokasiId, $jenisId, $sumberData)
{
    $target = 0;
    
    // --- 1. LOGIKA JENIS SAMPAH ---
    if ($sumberData == 'diserahkan') {
        $target = 3; // Semua 'Diserahkan' masuk ke Kolom 3 (Lainnya)
    } else {
        // Sampah Terkelola
        if ($jenisId == 1) {
            $target = 1; // Organik -> Kolom 1
        } 
        elseif ($jenisId == 2 || $jenisId == 3) {
            $target = 2; // Anorganik DAN Residu Terkelola -> Kolom 2 (Anorganik)
        }
    }

    // --- 2. LOGIKA LOKASI (Mapping ke Huruf Excel) ---
    // KANTOR (1) -> C, D, E
    if ($lokasiId == 1) return ($target==1 ? 'C' : ($target==2 ? 'D' : 'E'));
    
    // PARKIR (2) -> G, H, I
    if ($lokasiId == 2) return ($target==1 ? 'G' : ($target==2 ? 'H' : 'I'));
    
    // RUANG TUNGGU (3) -> K, L, M
    if ($lokasiId == 3) return ($target==1 ? 'K' : ($target==2 ? 'L' : 'M'));
    
    // TEMPAT MAKAN (4) -> O, P, Q
    if ($lokasiId == 4) return ($target==1 ? 'O' : ($target==2 ? 'P' : 'Q'));
    
    // KAPAL (5) -> S, T, U
    if ($lokasiId == 5) return ($target==1 ? 'S' : ($target==2 ? 'T' : 'U'));
    
    // AREA LAIN (6) -> W, X, Y
    if ($lokasiId == 6) return ($target==1 ? 'W' : ($target==2 ? 'X' : 'Y'));

    return null;
}

echo "=== SAMPAH DISERAHKAN RECORDS FOR YEAR 2025 ===\n";
try {
    $records = SampahDiserahkan::whereYear('tgl_diserahkan', 2025)->get();
    echo "Found " . $records->count() . " records.\n";
    foreach ($records as $row) {
        $tgl = (int)Carbon::parse($row->tgl_diserahkan)->format('d');
        $kolom = getExcelColumn($row->id_lokasi, $row->id_jenis, 'diserahkan');
        echo "ID: {$row->id}, Date: {$row->tgl_diserahkan->toDateString()}, Lokasi ID: {$row->id_lokasi}, Jenis ID: {$row->id_jenis}, Berat: {$row->jumlah_berat}, Excel Column: " . ($kolom ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== SAMPAH TERKELOLA RECORDS FOR YEAR 2025 ===\n";
try {
    $records = SampahTerkelola::whereYear('tgl', 2025)->get();
    echo "Found " . $records->count() . " records.\n";
    foreach ($records as $row) {
        $tgl = (int)Carbon::parse($row->tgl)->format('d');
        $kolom = getExcelColumn($row->id_lokasi, $row->id_jenis, 'terkelola');
        echo "ID: {$row->id}, Date: {$row->tgl->toDateString()}, Lokasi ID: {$row->id_lokasi}, Jenis ID: {$row->id_jenis}, Berat: {$row->jumlah_berat}, Excel Column: " . ($kolom ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
