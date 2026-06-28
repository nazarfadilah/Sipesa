<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\SampahTerkelola;
use App\Models\SampahDiserahkan;
use App\Models\Instansi;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanController extends Controller
{
    // === ATURAN BISNIS ===
    
    // 1. Mapping 6 Jenis Sampah ke 3 Kategori Excel
    const JENIS_ORGANIK_IDS = [1];
    const JENIS_ANORGANIK_IDS = [2];
    const JENIS_RESIDU_IDS = [3, 4, 5, 6];
    
    // 2. Nama Kolom Berat (sesuai Model - keduanya sama)
    const COL_TERKELOLA = 'jumlah_berat';
    const COL_DISERAHKAN = 'jumlah_berat';

    /**
     * Menampilkan halaman filter laporan
     */
    public function index(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        return view('admin.laporan.laporan', ['tahun' => $tahun]);
    }

    /**
     * Fungsi utama untuk mengekspor file 15-sheet atau hanya 12-sheet bulanan
     */
    public function export(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
        $tipe = $request->get('tipe', 'lengkap'); // 'lengkap' atau 'bulanan'
        
        $startDate = Carbon::createFromDate($tahun, 7, 1)->startOfDay();
        $endDate = $startDate->copy()->addYear()->subDay()->endOfDay();
        
        $lokasis = DB::table('lokasi_asals')->orderBy('id_lokasi')->pluck('nama_lokasi', 'id_lokasi');

        // Ambil data mentah 12 bulan
        $dataTerkelola = $this->queryFullData('sampah_terkelolas', $startDate, $endDate);
        $dataDiserahkan = $this->queryFullData('sampah_diserahkans', $startDate, $endDate);

        if ($tipe === 'bulanan') {
            // Export hanya 12 sheet data harian per bulan
            $dailyData = $this->getDailySheetsData($startDate, $dataTerkelola, $dataDiserahkan, $lokasis);
            $fileName = "Laporan_Bulanan_SIPESA_{$tahun}-" . ($tahun + 1) . ".xlsx";
            
            return Excel::download(new LaporanMultiSheetExport(
                null,  // tidak ada rekap neraca
                null,  // tidak ada rekap terkelola
                null,  // tidak ada rekap area
                $dailyData
            ), $fileName);
        } else {
            // Export lengkap 15 sheet
            $dailyData = $this->getDailySheetsData($startDate, $dataTerkelola, $dataDiserahkan, $lokasis);
            $rekapTerkelolaData = $this->getRekapTerkelolaData($dataTerkelola, $startDate);
            $rekapAreaData = $this->getRekapAreaData($dataTerkelola, $dataDiserahkan, $startDate, $lokasis);
            $rekapNeracaData = $this->getRekapNeracaData($dataTerkelola, $dataDiserahkan, $lokasis);

            $fileName = "Laporan_Logbook_SIPESA_{$tahun}-" . ($tahun + 1) . ".xlsx";

            return Excel::download(new LaporanMultiSheetExport(
                $rekapNeracaData,
                $rekapTerkelolaData,
                $rekapAreaData,
                $dailyData,
                $tahun
            ), $fileName);
        }
    }

    // ===================================================================
    // === HELPER PENGOLAH DATA ===
    // ===================================================================

    /**
     * Sheet 1: Rekap Neraca Pengelolaan Sampah
     */
    private function getRekapNeracaData($dataTerkelola, $dataDiserahkan, $lokasis)
    {
        $dataRekap = [];
        $grandTotals = [
            'timbulan' => 0,
            'terkelola_organik' => 0,
            'terkelola_anorganik' => 0,
            'total_terkelola' => 0,
            'residu_dll' => 0
        ];

        foreach ($lokasis as $id => $nama) {
            $terkelola_O = $dataTerkelola->where('id_lokasi', $id)->whereIn('id_jenis', self::JENIS_ORGANIK_IDS)->sum('jumlah_berat');
            $terkelola_A = $dataTerkelola->where('id_lokasi', $id)->whereIn('id_jenis', self::JENIS_ANORGANIK_IDS)->sum('jumlah_berat');
            $terkelola_R = $dataTerkelola->where('id_lokasi', $id)->whereIn('id_jenis', self::JENIS_RESIDU_IDS)->sum('jumlah_berat');
            $diserahkan_Total = $dataDiserahkan->where('id_lokasi', $id)->sum('jumlah_berat');
            
            $residu_dll = $terkelola_R + $diserahkan_Total;
            $total_terkelola = $terkelola_O + $terkelola_A;
            $timbulan = $total_terkelola + $residu_dll;
            
            $dataRekap[] = [
                'lokasi' => $nama,
                'timbulan' => $timbulan,
                'terkelola_organik' => $terkelola_O,
                'terkelola_anorganik' => $terkelola_A,
                'total_terkelola' => $total_terkelola,
                'residu_dll' => $residu_dll,
            ];

            $grandTotals['timbulan'] += $timbulan;
            $grandTotals['terkelola_organik'] += $terkelola_O;
            $grandTotals['terkelola_anorganik'] += $terkelola_A;
            $grandTotals['total_terkelola'] += $total_terkelola;
            $grandTotals['residu_dll'] += $residu_dll;
        }
        
        return ['data' => $dataRekap, 'totals' => $grandTotals, 'lokasis' => $lokasis];
    }

    /**
     * Sheet 2: Rekap Sampah Terkelola (HANYA dari sampah_terkelolas)
     */
    private function getRekapTerkelolaData($dataTerkelola, $startDate)
    {
        $dataRekap = [];
        $grandTotals = [
            'organik' => 0,
            'anorganik' => 0,
            'residu_dll' => 0,
            'timbulan_terkelola' => 0
        ];

        for ($i = 0; $i < 12; $i++) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $monthKey = $currentMonth->format('Y-m');
            
            $dataBulanan = $dataTerkelola->where('month_year', $monthKey);
            
            $terkelola_O = $dataBulanan->whereIn('id_jenis', self::JENIS_ORGANIK_IDS)->sum('jumlah_berat');
            $terkelola_A = $dataBulanan->whereIn('id_jenis', self::JENIS_ANORGANIK_IDS)->sum('jumlah_berat');
            $terkelola_R = $dataBulanan->whereIn('id_jenis', self::JENIS_RESIDU_IDS)->sum('jumlah_berat');
            $timbulan_terkelola = $terkelola_O + $terkelola_A + $terkelola_R;
            
            $dataRekap[] = [
                'bulan' => $this->translateMonth($currentMonth->format('F')),
                'tahun' => $currentMonth->format('Y'),
                'organik' => $terkelola_O,
                'anorganik' => $terkelola_A,
                'residu_dll' => $terkelola_R,
                'timbulan_terkelola' => $timbulan_terkelola,
            ];
            
            $grandTotals['organik'] += $terkelola_O;
            $grandTotals['anorganik'] += $terkelola_A;
            $grandTotals['residu_dll'] += $terkelola_R;
            $grandTotals['timbulan_terkelola'] += $timbulan_terkelola;
        }
        
        return ['data' => $dataRekap, 'totals' => $grandTotals];
    }

    /**
     * Sheet 3: Rekap Area (Pivot Area-Jenis)
     */
    private function getRekapAreaData($dataTerkelola, $dataDiserahkan, $startDate, $lokasis)
    {
        $dataRekap = [];
        $grandTotals = ['total_tahunan' => 0];
        
        for ($i = 0; $i < 12; $i++) {
            $currentMonth = $startDate->copy()->addMonths($i);
            $monthKey = $currentMonth->format('Y-m');
            
            $dataTerkelolaBulan = $dataTerkelola->where('month_year', $monthKey);
            $dataDiserahkanBulan = $dataDiserahkan->where('month_year', $monthKey);

            $rowData = [
                'bulan' => $this->translateMonth($currentMonth->format('F')),
                'tahun' => $currentMonth->format('Y')
            ];
            $totalBulanan = 0;
            
            foreach ($lokasis as $lokasiId => $lokasiNama) {
                $timbulan_O = $dataTerkelolaBulan->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_ORGANIK_IDS)->sum('jumlah_berat') +
                              $dataDiserahkanBulan->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_ORGANIK_IDS)->sum('jumlah_berat');
                
                $timbulan_A = $dataTerkelolaBulan->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_ANORGANIK_IDS)->sum('jumlah_berat') +
                              $dataDiserahkanBulan->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_ANORGANIK_IDS)->sum('jumlah_berat');
                
                $timbulan_R = $dataTerkelolaBulan->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_RESIDU_IDS)->sum('jumlah_berat') +
                              $dataDiserahkanBulan->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_RESIDU_IDS)->sum('jumlah_berat');

                $timbulan_Lokasi = $timbulan_O + $timbulan_A + $timbulan_R;
                
                $rowData["lokasi_{$lokasiId}_O"] = $timbulan_O;
                $rowData["lokasi_{$lokasiId}_A"] = $timbulan_A;
                $rowData["lokasi_{$lokasiId}_R"] = $timbulan_R;
                $rowData["lokasi_{$lokasiId}_Total"] = $timbulan_Lokasi;

                $totalBulanan += $timbulan_Lokasi;
                
                if (!isset($grandTotals["lokasi_{$lokasiId}_O"])) $grandTotals["lokasi_{$lokasiId}_O"] = 0;
                if (!isset($grandTotals["lokasi_{$lokasiId}_A"])) $grandTotals["lokasi_{$lokasiId}_A"] = 0;
                if (!isset($grandTotals["lokasi_{$lokasiId}_R"])) $grandTotals["lokasi_{$lokasiId}_R"] = 0;
                if (!isset($grandTotals["lokasi_{$lokasiId}_Total"])) $grandTotals["lokasi_{$lokasiId}_Total"] = 0;
                
                $grandTotals["lokasi_{$lokasiId}_O"] += $timbulan_O;
                $grandTotals["lokasi_{$lokasiId}_A"] += $timbulan_A;
                $grandTotals["lokasi_{$lokasiId}_R"] += $timbulan_R;
                $grandTotals["lokasi_{$lokasiId}_Total"] += $timbulan_Lokasi;
            }
            
            $rowData['total_bulanan'] = $totalBulanan;
            $grandTotals['total_tahunan'] += $totalBulanan;
            $dataRekap[] = $rowData;
        }
        
        return ['data' => $dataRekap, 'totals' => $grandTotals, 'lokasis' => $lokasis];
    }

    /**
     * Sheet 4-15: Data Harian (12 bulan)
     */
    private function getDailySheetsData($startDate, $dataTerkelola, $dataDiserahkan, $lokasis)
    {
        $dailyDataPerMonth = [];
        
        for ($i = 0; $i < 12; $i++) {
            $startOfMonth = $startDate->copy()->addMonths($i);
            $monthName = $startOfMonth->format('F');
            $monthKey = $startOfMonth->format('Y-m');
            $tahunBulan = $startOfMonth->format('Y');
            $bulanIndo = $this->translateMonth($monthName);

            $dataTerkelolaBulan = $dataTerkelola->where('month_year', $monthKey);
            $dataDiserahkanBulan = $dataDiserahkan->where('month_year', $monthKey);

            $monthData = [];
            $monthTotals = $this->getEmptyPivotTotals($lokasis);

            for ($day = 1; $day <= $startOfMonth->daysInMonth; $day++) {
                $currentDate = $startOfMonth->copy()->addDays($day - 1);
                $dateString = $currentDate->toDateString();
                
                $dataTerkelolaHari = $dataTerkelolaBulan->where('tgl', $dateString);
                $dataDiserahkanHari = $dataDiserahkanBulan->where('tgl', $dateString);

                $rowData = ['tanggal' => $currentDate->format('d-m-Y')];
                $totalHarian = 0;

                foreach ($lokasis as $lokasiId => $lokasiNama) {
                    $timbulan_O = $dataTerkelolaHari->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_ORGANIK_IDS)->sum('jumlah_berat') +
                                  $dataDiserahkanHari->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_ORGANIK_IDS)->sum('jumlah_berat');
                    
                    $timbulan_A = $dataTerkelolaHari->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_ANORGANIK_IDS)->sum('jumlah_berat') +
                                  $dataDiserahkanHari->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_ANORGANIK_IDS)->sum('jumlah_berat');
                    
                    $timbulan_R = $dataTerkelolaHari->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_RESIDU_IDS)->sum('jumlah_berat') +
                                  $dataDiserahkanHari->where('id_lokasi', $lokasiId)->whereIn('id_jenis', self::JENIS_RESIDU_IDS)->sum('jumlah_berat');

                    $timbulan_Lokasi = $timbulan_O + $timbulan_A + $timbulan_R;

                    $rowData["lokasi_{$lokasiId}_O"] = $timbulan_O;
                    $rowData["lokasi_{$lokasiId}_A"] = $timbulan_A;
                    $rowData["lokasi_{$lokasiId}_R"] = $timbulan_R;
                    $rowData["lokasi_{$lokasiId}_Total"] = $timbulan_Lokasi;

                    $totalHarian += $timbulan_Lokasi;

                    $monthTotals["lokasi_{$lokasiId}_O"] += $timbulan_O;
                    $monthTotals["lokasi_{$lokasiId}_A"] += $timbulan_A;
                    $monthTotals["lokasi_{$lokasiId}_R"] += $timbulan_R;
                    $monthTotals["lokasi_{$lokasiId}_Total"] += $timbulan_Lokasi;
                }
                
                $rowData['total_harian'] = $totalHarian;
                $monthTotals['total_bulanan'] += $totalHarian;
                $monthData[] = $rowData;
            }
            
            $dailyDataPerMonth[$monthName] = [
                'data' => $monthData, 
                'totals' => $monthTotals,
                'tahun' => $tahunBulan,
                'bulan' => $bulanIndo
            ];
        }
        
        return ['dailyData' => $dailyDataPerMonth, 'lokasis' => $lokasis];
    }

    // ===================================================================
    // === HELPER QUERY DATABASE ===
    // ===================================================================

    /**
     * Mengambil semua data mentah dari DB untuk 12 bulan
     */
    private function queryFullData($table, $start, $end)
    {
        // Tentukan kolom tanggal berdasarkan tabel
        $dateColumn = ($table === 'sampah_diserahkans') ? 'tgl_diserahkan' : 'tgl';
        
        return DB::table($table)
            ->whereBetween($dateColumn, [$start, $end])
            ->select($dateColumn . ' as tgl', 'id_jenis', 'id_lokasi', 'jumlah_berat')
            ->get()->map(function ($item) {
                $date = Carbon::parse($item->tgl);
                $item->tgl = $date->toDateString();
                $item->month_year = $date->format('Y-m');
                return $item;
            });
    }

    /**
     * Helper untuk membuat array total pivot yang kosong
     */
    private function getEmptyPivotTotals($lokasis)
    {
        $totals = ['total_bulanan' => 0];
        foreach ($lokasis as $lokasiId => $lokasiNama) {
            $totals["lokasi_{$lokasiId}_O"] = 0;
            $totals["lokasi_{$lokasiId}_A"] = 0;
            $totals["lokasi_{$lokasiId}_R"] = 0;
            $totals["lokasi_{$lokasiId}_Total"] = 0;
        }
        return $totals;
    }

    /**
     * Helper untuk translate nama bulan ke Indonesia
     */
    private function translateMonth($monthName)
    {
        $months = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];
        return $months[$monthName] ?? $monthName;
    }

    /**
     * Menampilkan Halaman Form Export (SuperAdmin-style)
     * Admin dapat memilih instansi mana untuk export
     */
    public function showExportForm()
    {
        $instansiList = Instansi::all();
        return view('admin.laporan.export-laporan', compact('instansiList'));
    }

    /**
     * PROSES UTAMA EXPORT (Template Injection - sama seperti SuperAdmin)
     * Admin dapat export data dari multiple instansi yang dipilih
     */
    public function processExport(Request $request)
    {
        set_time_limit(600);
        ini_set('memory_limit', '1024M');

        // --- 1. VALIDASI INPUT ---
        $request->validate([
            'tahun' => 'required',
            'type' => 'required|in:tahunan,bulanan',
            'id_instansi' => 'required|array',
            'id_instansi.*' => 'required|exists:instansis,id_instansi'
        ]);

        $type = $request->input('type');
        $tahunInput = $request->input('tahun');
        
        // Admin dapat memilih multiple instansi untuk export
        $instansiIds = $request->input('id_instansi');

        // --- 2. SIAPKAN NAMA INSTANSI (Untuk Header Excel) ---
        $namaInstansiList = Instansi::whereIn('id_instansi', $instansiIds)
            ->pluck('nama_instansi')
            ->toArray();
            
        $teksInstansi = "";
        if (count($namaInstansiList) == 1) {
            $teksInstansi = strtoupper($namaInstansiList[0]);
        } else {
            $teksInstansi = "SELURUH WILAYAH OPERASIONAL";
        }

        try {
            // =========================================================
            // SKENARIO 1: EXPORT BULANAN (Cloning Sheet)
            // =========================================================
            if ($type == 'bulanan') {
                $request->validate(['bulan' => 'required|array']);
                $bulanDipilih = $request->input('bulan');

                // Load Template
                $templatePath = storage_path('app/public/templates/template_bulanan_master.xlsx');
                if (!file_exists($templatePath)) return back()->with('error', 'File template_bulanan_master.xlsx tidak ditemukan!');
                
                $spreadsheet = IOFactory::load($templatePath);
                $masterSheet = $spreadsheet->getSheetByName('Rekap Bulanan');
                if (!$masterSheet) return back()->with('error', 'Sheet "Rekap Bulanan" tidak ditemukan di template!');

                foreach ($bulanDipilih as $bln) {
                    $bulanNama = $this->getNamaBulan($bln);
                    
                    // Laporan Bulanan menggunakan Tahun Kalender secara langsung
                    $currentYear = $tahunInput;
                    
                    // 1. Clone Sheet Master
                    $clonedSheet = clone $masterSheet;
                    $clonedSheet->setTitle(substr($bulanNama, 0, 30)); // Max 31 karakter
                    $spreadsheet->addSheet($clonedSheet);
                    
                    // 2. Isi Header (Sesuai Layout Template)
                    // Template: A2="Instansi:", A3="Tahun:", A4="Bulan:"
                    $clonedSheet->setCellValue('B2', $teksInstansi);
                    $clonedSheet->setCellValue('B3', $currentYear);
                    $clonedSheet->setCellValue('B4', strtoupper($bulanNama));

                    // 3. Isi Data (Start Row 9)
                    $this->isiDataKeSheet($clonedSheet, $currentYear, $bln, $instansiIds, 9);
                }

                // Hapus Master Sheet (Sisa Cloning)
                $sheetIndex = $spreadsheet->getIndex($masterSheet);
                $spreadsheet->removeSheetByIndex($sheetIndex);
                $spreadsheet->setActiveSheetIndex(0);

                $fileName = 'Laporan_Bulanan_Admin_' . $tahunInput . '.xlsx';
            } 
            
            // =========================================================
            // SKENARIO 2: EXPORT TAHUNAN (Full 12 Bulan)
            // =========================================================
            else {
                $templatePath = storage_path('app/public/templates/template_master.xlsx');
                if (!file_exists($templatePath)) return back()->with('error', 'File template_master.xlsx tidak ditemukan!');

                $spreadsheet = IOFactory::load($templatePath);

                // Update Header Rekap Neraca
                $sheetNeraca = $spreadsheet->getSheetByName('Rekap Neraca Pengelolaan Sampah');
                if ($sheetNeraca) {
                    $sheetNeraca->setCellValue('D2', $tahunInput - 1); // Tahun Awal
                    $sheetNeraca->setCellValue('G2', $tahunInput);     // Tahun Akhir
                    $sheetNeraca->setCellValue('C4', $teksInstansi);   // Nama Instansi
                    
                    // Fix template bug: Rekap Neraca memiliki referensi kolom Rekap area yang salah
                    // untuk Ruang Tunggu, Tempat Makan, Kapal, dan Area Lain
                    // Kolom Rekap area yang benar:
                    // Kantor: D,E,F | Parkir: H,I,J | Ruang Tunggu: L,M,N
                    // Tempat Makan: P,Q,R | Kapal: T,U,V | Area Lain: X,Y,Z
                    
                    // Row 9: Ruang Tunggu (template salah: D20,E20,F20 -> benar: L20,M20,N20)
                    $sheetNeraca->setCellValue('E9', "='Rekap area'!\$L\$20");
                    $sheetNeraca->setCellValue('F9', "='Rekap area'!\$M\$20");
                    $sheetNeraca->setCellValue('I9', "='Rekap area'!\$N\$20");
                    
                    // Row 10: Tempat Makan (template salah: H20,I20,J20 -> benar: P20,Q20,R20)
                    $sheetNeraca->setCellValue('E10', "='Rekap area'!\$P\$20");
                    $sheetNeraca->setCellValue('F10', "='Rekap area'!\$Q\$20");
                    $sheetNeraca->setCellValue('I10', "='Rekap area'!\$R\$20");
                    
                    // Row 11: Kapal (template salah: L20,M20,N20 -> benar: T20,U20,V20)
                    $sheetNeraca->setCellValue('E11', "='Rekap area'!\$T\$20");
                    $sheetNeraca->setCellValue('F11', "='Rekap area'!\$U\$20");
                    $sheetNeraca->setCellValue('I11', "='Rekap area'!\$V\$20");
                    
                    // Row 12: Area Lain (template salah: P20,Q20,R20 -> benar: X20,Y20,Z20)
                    $sheetNeraca->setCellValue('E12', "='Rekap area'!\$X\$20");
                    $sheetNeraca->setCellValue('F12', "='Rekap area'!\$Y\$20");
                    $sheetNeraca->setCellValue('I12', "='Rekap area'!\$Z\$20");

                    // Memperbaiki Baris Total (Row 13) agar menjumlahkan baris 7 s.d 12
                    $sheetNeraca->setCellValue('E13', "=SUM(E7:E12)");
                    $sheetNeraca->setCellValue('F13', "=SUM(F7:F12)");
                    $sheetNeraca->setCellValue('I13', "=SUM(I7:I12)");

                    // Mengosongkan Baris 14 (sisa formula salah bawaan template)
                    $sheetNeraca->setCellValue('E14', null);
                    $sheetNeraca->setCellValue('F14', null);
                    $sheetNeraca->setCellValue('I14', null);
                }

                // Loop 12 Bulan (Logika Neraca: Juli - Juni)
                $months = [
                    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 
                    11 => 'November', 12 => 'Desember',
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
                    5 => 'Mei', 6 => 'Juni'
                ];

                foreach ($months as $monthNum => $sheetName) {
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    if (!$sheet) continue;
                    
                    // Juli-Des = Tahun Lalu, Jan-Juni = Tahun Ini
                    $currentYear = ($monthNum >= 7) ? ($tahunInput - 1) : $tahunInput;
                    
                    // Isi Data (Start Row 8)
                    $this->isiDataKeSheet($sheet, $currentYear, $monthNum, $instansiIds, 8);
                }
                
                $fileName = 'Laporan_Neraca_Admin_' . $tahunInput . '.xlsx';
            }

            // OUTPUT DOWNLOAD
            $writer = new Xlsx($spreadsheet);
            return response()->streamDownload(function() use ($writer) {
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'System Error: ' . $e->getMessage());
        }
    }

    /**
     * HELPER: QUERY DATABASE & TULIS KE CELL EXCEL
     * (Sama dengan SuperAdmin)
     */
    private function isiDataKeSheet($sheet, $year, $month, $instansiIds, $startRow)
    {
        // Cari semua user yang terkait dengan instansi yang dipilih
        // Termasuk: petugas (id_instansi != null) DAN admin (id_instansi null yang memasukkan data)
        // Untuk keamanan: jika instansi tidak dipilih, ambil SEMUA data
        $userIdsInInstansi = \App\Models\User::whereIn('id_instansi', $instansiIds)
            ->orWhereNull('id_instansi') // Include admin users who also input data
            ->pluck('id')
            ->toArray();

        // 1. DATA SAMPAH TERKELOLA
        $dataTerkelola = SampahTerkelola::whereYear('tgl', $year)
            ->whereMonth('tgl', $month)
            ->whereIn('id_user', $userIdsInInstansi)
            ->get();

        foreach ($dataTerkelola as $row) {
            $tgl = (int)Carbon::parse($row->tgl)->format('d');
            
            // Rumus Baris: StartRow + (Tanggal - 1)
            $baris = $startRow + ($tgl - 1);
            
            $kolom = $this->getExcelColumn($row->id_lokasi, $row->id_jenis, 'terkelola');
            
            if ($kolom) {
                $val = $sheet->getCell($kolom . $baris)->getValue();
                if (!is_numeric($val)) $val = 0;
                $sheet->setCellValue($kolom . $baris, (float)$val + (float)$row->jumlah_berat);
            }
        }

        // 2. DATA SAMPAH DISERAHKAN
        $dataDiserahkan = SampahDiserahkan::whereYear('tgl_diserahkan', $year)
            ->whereMonth('tgl_diserahkan', $month)
            ->whereIn('id_user', $userIdsInInstansi)
            ->get();

        foreach ($dataDiserahkan as $row) {
            $tgl = (int)Carbon::parse($row->tgl_diserahkan)->format('d');
            $baris = $startRow + ($tgl - 1);
            
            // Semua sampah diserahkan masuk ke Kolom 3 (Residu/Lainnya)
            $kolom = $this->getExcelColumn($row->id_lokasi, $row->id_jenis, 'diserahkan');
            
            if ($kolom) {
                $val = $sheet->getCell($kolom . $baris)->getValue();
                if (!is_numeric($val)) $val = 0;
                $sheet->setCellValue($kolom . $baris, (float)$val + (float)$row->jumlah_berat);
            }
        }
    }

    /**
     * HELPER: MAPPING KOLOM EXCEL
     * (Sama dengan SuperAdmin)
     * Aturan Bisnis:
     * 1. Diserahkan -> Kolom 3 (Lainnya/Residu)
     * 2. Terkelola Organik -> Kolom 1
     * 3. Terkelola Anorganik & Residu -> Kolom 2
     */
    private function getExcelColumn($lokasiId, $jenisId, $sumberData)
    {
        $target = 0;
        
        // --- 1. LOGIKA JENIS SAMPAH ---
        if ($sumberData == 'diserahkan') {
            // Semua 'Diserahkan' masuk ke Kolom 3 (Lainnya/Residu)
            $target = 3;
        } else {
            // Sampah Terkelola: mapping berdasarkan id_jenis
            // Organik (id_jenis=1) -> Kolom 1
            // Anorganik (id_jenis=2) -> Kolom 2
            // Residu/Lainnya (id_jenis=3,4,5,6,...) -> Kolom 2
            if ($jenisId == 1) {
                $target = 1; // Organik -> Kolom 1
            } else {
                // Semua jenis selain organik (anorganik, residu, dll) -> Kolom 2
                $target = 2;
            }
        }

        // --- 2. LOGIKA LOKASI (Mapping ke Huruf Excel) ---
        // Sesuai template: setiap lokasi punya 3 kolom data + 1 kolom total
        // KANTOR (1) -> C(Org), D(Anorg), E(Residu)
        if ($lokasiId == 1) return ($target==1 ? 'C' : ($target==2 ? 'D' : 'E'));
        
        // PARKIR (2) -> G(Org), H(Anorg), I(Residu)
        if ($lokasiId == 2) return ($target==1 ? 'G' : ($target==2 ? 'H' : 'I'));
        
        // RUANG TUNGGU (3) -> K(Org), L(Anorg), M(Residu)
        if ($lokasiId == 3) return ($target==1 ? 'K' : ($target==2 ? 'L' : 'M'));
        
        // TEMPAT MAKAN (4) -> O(Org), P(Anorg), Q(Residu)
        if ($lokasiId == 4) return ($target==1 ? 'O' : ($target==2 ? 'P' : 'Q'));
        
        // KAPAL (5) -> S(Org), T(Anorg), U(Residu)
        if ($lokasiId == 5) return ($target==1 ? 'S' : ($target==2 ? 'T' : 'U'));
        
        // AREA LAIN (6) -> W(Org), X(Anorg), Y(Residu)
        if ($lokasiId == 6) return ($target==1 ? 'W' : ($target==2 ? 'X' : 'Y'));

        // Jika id_lokasi tidak dikenal, return null (data akan di-skip)
        return null;
    }

    /**
     * HELPER: Nama Bulan
     * (Sama dengan SuperAdmin)
     */
    private function getNamaBulan($num)
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $bulan[$num] ?? '';
    }
}

