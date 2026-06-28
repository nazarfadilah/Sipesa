<?php
// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\SampahTerkelola;
use App\Models\SampahDiserahkan;
use App\Models\User;
use App\Models\Instansi;

echo "--- INSTANSI DATA ---\n";
foreach (Instansi::all() as $instansi) {
    echo "ID: {$instansi->id_instansi} | Nama: {$instansi->nama_instansi}\n";
}

echo "\n--- USERS DATA ---\n";
foreach (User::all() as $user) {
    echo "ID: {$user->id} | Name: {$user->name} | Instansi ID: {$user->id_instansi}\n";
}

echo "\n--- SAMPAH TERKELOLA (2025) count: " . SampahTerkelola::whereYear('tgl', 2025)->count() . " ---\n";
$terkelola = SampahTerkelola::whereYear('tgl', 2025)->with('user.instansi')->take(10)->get();
foreach ($terkelola as $t) {
    echo "ID: {$t->id} | Tgl: " . ($t->tgl ? $t->tgl->format('Y-m-d') : 'NULL') . " | User: " . ($t->user ? $t->user->name : 'NULL') . " (Instansi: " . (($t->user && $t->user->id_instansi) ? $t->user->id_instansi : 'NULL') . ") | Berat: {$t->jumlah_berat} | Lokasi: {$t->id_lokasi} | Jenis: {$t->id_jenis}\n";
}

echo "\n--- SAMPAH DISERAHKAN (2025) count: " . SampahDiserahkan::whereYear('tgl_diserahkan', 2025)->count() . " ---\n";
$diserahkan = SampahDiserahkan::whereYear('tgl_diserahkan', 2025)->with('user.instansi')->take(10)->get();
foreach ($diserahkan as $d) {
    echo "ID: {$d->id} | Tgl: " . ($d->tgl_diserahkan ? $d->tgl_diserahkan->format('Y-m-d') : 'NULL') . " | User: " . ($d->user ? $d->user->name : 'NULL') . " (Instansi: " . (($d->user && $d->user->id_instansi) ? $d->user->id_instansi : 'NULL') . ") | Berat: {$d->jumlah_berat} | Lokasi: {$d->id_lokasi} | Jenis: {$d->id_jenis}\n";
}
