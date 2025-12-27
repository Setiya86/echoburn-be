<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Carbon\Carbon; // Wajib import Carbon untuk manipulasi tanggal

class PembayaranController extends Controller
{
    /**
     * READ (Index) - Menampilkan SEMUA DATA dengan filter opsional.
     * @param Request $request menerima filter: dateFrom, dateTo, sumber, status.
     */
    public function index(Request $request)
    {
        $query = Pembayaran::with('pelanggan');

        // --- Perbaikan Filter Tanggal (Mengatasi Date Shift & Data Collision) ---
        
        // Filter Tanggal Awal (dateFrom)
        if ($request->has('dateFrom') && $request->dateFrom) {
            // Mengambil tanggal dari FE dan memastikan waktu adalah 00:00:00 (awal hari)
            $dateFrom = Carbon::parse($request->dateFrom)->startOfDay(); 
            $query->where('tanggal_pembayaran', '>=', $dateFrom);
        }

        // Filter Tanggal Akhir (dateTo)
        if ($request->has('dateTo') && $request->dateTo) {
            // Mengambil tanggal dari FE dan memastikan waktu adalah 23:59:59 (akhir hari)
            // INI MEMPERBAIKI DATA TABRAKAN (Data hari terakhir pasti masuk)
            $dateTo = Carbon::parse($request->dateTo)->endOfDay();
            $query->where('tanggal_pembayaran', '<=', $dateTo);
        }
        
        // --- Filter Sumber Pemasukan ---
        if ($request->has('sumber') && $request->sumber && $request->sumber !== 'semua') {
            $query->where('sumber_pemasukan', $request->sumber);
        }

        // --- Filter Status Pembayaran ---
        if ($request->has('status') && $request->status && $request->status !== 'semua') {
            $query->where('status_pembayaran', $request->status);
        }

        // Diurutkan dari tanggal pembayaran terbaru
        $pembayaran = $query->orderBy('tanggal_pembayaran', 'desc')->get();

        return response()->json($pembayaran);
    }

    /**
     * CREATE (Store) - Menambah data transaksi.
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $validated = $request->validate([
            'pelanggan_id'      => 'required|exists:pelanggan,pelanggan_id', 
            'sumber_pemasukan'  => 'required|in:pendaftaran,perpanjang,ditempat',
            'status_pembayaran' => 'nullable|in:lunas,pending,batal',
            'jumlah_bayar'      => 'required|numeric|min:0',
            
            // Perbaikan: Jika tanggal tidak dikirim dari FE, gunakan tanggal sekarang
            'tanggal_pembayaran'=> 'nullable|date', 
            
            'keterangan'        => 'nullable|string'
        ]);
        
        // Set tanggal pembayaran jika tidak ada (default ke sekarang)
        if (!isset($validated['tanggal_pembayaran'])) {
            $validated['tanggal_pembayaran'] = Carbon::now();
        }

        // 2. Simpan
        $pembayaran = Pembayaran::create($validated);

        // 3. Return response dengan data relasinya
        return response()->json([
            'message' => 'Pembayaran berhasil disimpan',
            'data'    => $pembayaran->load('pelanggan')
        ], 201);
    }

    /**
     * READ (Show) - Menampilkan detail satu data.
     */
    public function show($id)
    {
        $pembayaran = Pembayaran::with('pelanggan')->findOrFail($id);
        return response()->json($pembayaran);
    }

    /**
     * UPDATE (Update) - Mengubah data transaksi.
     */
    public function update(Request $request, $id)
    {
        $pembayaran = Pembayaran::findOrFail($id);

        // 1. Validasi
        $validated = $request->validate([
            'pelanggan_id'      => 'sometimes|exists:pelanggan,pelanggan_id',
            'sumber_pemasukan'  => 'sometimes|in:pendaftaran,perpanjang,ditempat',
            'status_pembayaran' => 'sometimes|in:lunas,pending,batal',
            'jumlah_bayar'      => 'sometimes|numeric|min:0',
            'tanggal_pembayaran'=> 'sometimes|date',
            'keterangan'        => 'nullable|string'
        ]);

        // 2. Update
        $pembayaran->update($validated);

        return response()->json([
            'message' => 'Data pembayaran diperbarui',
            'data'    => $pembayaran->load('pelanggan')
        ]);
    }

    /**
     * DELETE (Destroy) - Menghapus data transaksi.
     */
    public function destroy($id)
    {
        $pembayaran = Pembayaran::findOrFail($id);
        $pembayaran->delete();

        return response()->json(['message' => 'Data pembayaran dihapus']);
    }
}