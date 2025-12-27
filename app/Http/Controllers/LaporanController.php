<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index()
    {
        // Menampilkan laporan diurutkan dari yang terbaru
        return response()->json(
            Laporan::with(['user', 'aktivitas'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function store(Request $request)
    {
        // 1. Validasi Input
        $validatedData = $request->validate([
            // Pastikan aktivitas_id ada di tabel aktivitas
            'aktivitas_id'                  => 'required|exists:aktivitas,aktivitas_id', 
            'jenis_laporan'                 => 'required|string|max:255',
            'tanggal'                       => 'required|date',
            'volume_sampah_masuk'           => 'required|numeric|min:0',
            'volume_sampah_setelah_dibakar' => 'nullable|numeric|min:0',
        ]);

        // 2. Tambahkan User ID otomatis dari Token Login
        $validatedData['user_id'] = auth()->id();

        // 3. Simpan
        $laporan = Laporan::create($validatedData);

        return response()->json([
            'message' => 'Laporan berhasil dibuat',
            'data'    => $laporan
        ], 201);
    }

    public function show($id)
    {
        // Menggunakan findOrFail yang otomatis membaca 'laporan_id' dari Model
        $laporan = Laporan::with(['user', 'aktivitas'])->findOrFail($id);
        
        return response()->json($laporan);
    }

    public function update(Request $request, $id)
    {
        $laporan = Laporan::findOrFail($id);

        // Validasi (Nullable/Sometimes untuk update parsial)
        $validatedData = $request->validate([
            'aktivitas_id'                  => 'sometimes|exists:aktivitas,aktivitas_id',
            'jenis_laporan'                 => 'sometimes|string',
            'tanggal'                       => 'sometimes|date',
            'volume_sampah_masuk'           => 'sometimes|numeric',
            'volume_sampah_setelah_dibakar' => 'sometimes|numeric',
        ]);

        $laporan->update($validatedData);

        return response()->json([
            'message' => 'Laporan berhasil diperbarui',
            'data'    => $laporan
        ]);
    }

    public function destroy($id)
    {
        $laporan = Laporan::findOrFail($id);
        $laporan->delete();

        return response()->json(['message' => 'Laporan berhasil dihapus']);
    }
}