<?php

namespace App\Http\Controllers\Api\V1\Petugas;

use App\Http\Controllers\Controller;
use App\Models\SampahTerkelola;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SampahTerkelolaController extends Controller
{
    /**
     * GET /api/v1/sampah-terkelola
     * Get all waste management records with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        $userId = Auth::id();
        
        $sampah = SampahTerkelola::where('id_user', $userId)
            ->with(['lokasiAsal', 'jenis'])
            ->orderBy('tgl', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $sampah->items(),
            'total' => $sampah->total(),
            'per_page' => $sampah->perPage(),
            'current_page' => $sampah->currentPage(),
            'last_page' => $sampah->lastPage(),
        ]);
    }

    /**
     * POST /api/v1/sampah-terkelola
     * Create new waste management record
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tgl' => 'required|date',
            'id_lokasi' => 'required|exists:lokasi_asals,id_lokasi',
            'id_jenis' => 'required|exists:jenis,id_jenis',
            'jumlah_berat' => 'required|numeric|min:0.01',
            'foto_kelola' => 'nullable|image|mimes:jpeg,png,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sampah = new SampahTerkelola();
            $sampah->tgl = $request->tgl;
            $sampah->id_lokasi = $request->id_lokasi;
            $sampah->id_jenis = $request->id_jenis;
            $sampah->jumlah_berat = $request->jumlah_berat;
            $sampah->id_user = Auth::id();

            if ($request->hasFile('foto_kelola')) {
                $sampah->foto_kelola = $request->file('foto_kelola')->store('sampah_terkelola', 'public');
            }

            $sampah->save();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil ditambahkan',
                'data' => $sampah,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/sampah-terkelola/{id}
     * Get single waste management record
     */
    public function show($id)
    {
        $sampah = SampahTerkelola::where('id_user', Auth::id())
            ->with(['lokasiAsal', 'jenis'])
            ->find($id);

        if (!$sampah) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sampah,
        ]);
    }

    /**
     * PUT /api/v1/sampah-terkelola/{id}
     * Update waste management record
     */
    public function update(Request $request, $id)
    {
        $sampah = SampahTerkelola::where('id_user', Auth::id())->find($id);

        if (!$sampah) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tgl' => 'required|date',
            'id_lokasi' => 'required|exists:lokasi_asals,id_lokasi',
            'id_jenis' => 'required|exists:jenis,id_jenis',
            'jumlah_berat' => 'required|numeric|min:0.01',
            'alasan_edit' => 'nullable|string',
            'foto_kelola' => 'nullable|image|mimes:jpeg,png,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sampah->tgl = $request->tgl;
            $sampah->id_lokasi = $request->id_lokasi;
            $sampah->id_jenis = $request->id_jenis;
            $sampah->jumlah_berat = $request->jumlah_berat;
            $sampah->alasan_edit = $request->alasan_edit;

            if ($request->hasFile('foto_kelola')) {
                if ($sampah->foto_kelola) {
                    \Storage::disk('public')->delete($sampah->foto_kelola);
                }
                $sampah->foto_kelola = $request->file('foto_kelola')->store('sampah_terkelola', 'public');
            }

            $sampah->save();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui',
                'data' => $sampah,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
