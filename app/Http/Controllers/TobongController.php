<?php

namespace App\Http\Controllers;

use App\Models\Tobong;
use Illuminate\Http\Request;

class TobongController extends Controller
{
    // MENAMPILKAN DATA
    public function index()
    {
        return response()->json(Tobong::all());
    }

    // MENYIMPAN DATA BARU
    public function store(Request $request)
    {
        // 1. Validasi Input sesuai Migration baru
        $validatedData = $request->validate([
            'nama_tobong'        => 'required|string|max:255', // Wajib ada
            'lokasi'             => 'required|string|max:255',
            'tanggal_pembuatan'  => 'nullable|date',    // Ubah jadi date
            'kapasitas'          => 'nullable|integer', // Ganti nama dari kapasitas_pembakaran
            'kapasitas_abu'      => 'nullable|integer',
            'status_operasional' => 'nullable|string|in:aktif,perbaikan,non-aktif', // Opsional: validasi enum manual
        ]);

        // 2. Simpan Data
        $tobong = Tobong::create($validatedData);

        return response()->json([
            'message' => 'Data Tobong berhasil disimpan',
            'data'    => $tobong
        ], 201);
    }

    // MENAMPILKAN DETAIL
    public function show($id)
    {
        $tobong = Tobong::findOrFail($id);
        return response()->json($tobong);
    }

    // UPDATE DATA
    public function update(Request $request, $id)
    {
        $tobong = Tobong::findOrFail($id);

        // 1. Validasi (gunakan 'sometimes' agar user tidak wajib kirim semua data)
        $validatedData = $request->validate([
            'nama_tobong'        => 'sometimes|required|string|max:255',
            'lokasi'             => 'sometimes|required|string|max:255',
            'tanggal_pembuatan'  => 'nullable|date',
            'kapasitas'          => 'nullable|integer',
            'kapasitas_abu'      => 'nullable|integer',
            'status_operasional' => 'nullable|string',
        ]);

        // 2. Update Data
        $tobong->update($validatedData);

        return response()->json([
            'message' => 'Data Tobong diperbarui',
            'data'    => $tobong
        ]);
    }

    // HAPUS DATA
    public function destroy($id)
    {
        $tobong = Tobong::findOrFail($id);
        $tobong->delete();

        return response()->json(['message' => 'Data Tobong dihapus']);
    }

    public function lookup()
    {
        // Hanya ambil ID dan Nama untuk performa dropdown yang cepat
        $tobongList = Tobong::select('tobong_id', 'nama_tobong')->get();

        // Mengembalikan data mentah
        return response()->json($tobongList); 
    }
}