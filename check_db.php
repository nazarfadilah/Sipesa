<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SampahDiserahkan;
use App\Models\SampahTerkelola;

echo "--- SAMPAH DISERAHKAN COUNT BY YEAR-MONTH ---\n";
$diserahkan = SampahDiserahkan::selectRaw('YEAR(tgl_diserahkan) as yr, MONTH(tgl_diserahkan) as mon, count(*) as cnt')
    ->groupBy('yr', 'mon')
    ->orderBy('yr')
    ->orderBy('mon')
    ->get();
foreach ($diserahkan as $d) {
    echo "Year: {$d->yr}, Month: {$d->mon}, Count: {$d->cnt}\n";
}

echo "\n--- SAMPAH TERKELOLA COUNT BY YEAR-MONTH ---\n";
$terkelola = SampahTerkelola::selectRaw('YEAR(tgl) as yr, MONTH(tgl) as mon, count(*) as cnt')
    ->groupBy('yr', 'mon')
    ->orderBy('yr')
    ->orderBy('mon')
    ->get();
foreach ($terkelola as $t) {
    echo "Year: {$t->yr}, Month: {$t->mon}, Count: {$t->cnt}\n";
}
