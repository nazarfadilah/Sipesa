<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
echo "Terkelola:\n";
$terkelola = DB::table('sampah_terkelolas')->select(DB::raw('YEAR(tgl) as yr, MONTH(tgl) as mth, count(*) as count, sum(jumlah_berat) as total_berat'))->groupBy('yr', 'mth')->get();
foreach ($terkelola as $t) {
    echo "Year: {$t->yr}, Month: {$t->mth}, Count: {$t->count}, Total: {$t->total_berat}\n";
}

echo "\nDiserahkan:\n";
$diserahkan = DB::table('sampah_diserahkans')->select(DB::raw('YEAR(tgl_diserahkan) as yr, MONTH(tgl_diserahkan) as mth, count(*) as count, sum(jumlah_berat) as total_berat'))->groupBy('yr', 'mth')->get();
foreach ($diserahkan as $d) {
    echo "Year: {$d->yr}, Month: {$d->mth}, Count: {$d->count}, Total: {$d->total_berat}\n";
}
