<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Wajib untuk fitur Transaksi

class DaftarAnggotaController extends Controller
{
    /**
     * READ (Index)
     * Menampilkan daftar semua anggota
     */
    public function index()
    {
        // Mengambil data pelanggan diurutkan dari yang terbaru bergabung
        $pelanggan = Pelanggan::orderBy('created_at', 'desc')->get();

        return response()->json($pelanggan);
    }

    /**
     * CREATE (Store)
     * Mendaftarkan anggota + Mencatat pembayaran pendaftaran (ATOMIC TRANSACTION)
     */
    public function store(Request $request)
    {
        // 1. Validasi Input Gabungan (Profil + Uang)
        $validated = $request->validate([
            // Data Pelanggan
            'nama_lengkap'     => 'required|string|max:255',
            'alamat'           => 'nullable|string',
            'nomor_telepon'    => 'nullable|string|max:20',
            'email'            => 'nullable|email|unique:pelanggan,email',
            'status_pelanggan' => 'nullable|in:aktif,masa_tenggang,nonaktif',
            
            // Data Pembayaran
            'jumlah_bayar'     => 'required|numeric|min:0',
            'keterangan'       => 'nullable|string', // Opsional, misal: "Daftar via Sales A"
        ]);

        // 2. Mulai Transaksi Database
        try {
            $result = DB::transaction(function () use ($validated) {
                
                // A. Simpan Data ke Tabel Pelanggan
                $pelanggan = Pelanggan::create([
                    'nama_lengkap'     => $validated['nama_lengkap'],
                    'alamat'           => $validated['alamat'] ?? null,
                    'nomor_telepon'    => $validated['nomor_telepon'] ?? null,
                    'email'            => $validated['email'] ?? null,
                    'status_pelanggan' => $validated['status_pelanggan'] ?? 'aktif',
                    'jumlah_sampah_sudah_dibakar' => 0, // Default 0 untuk member baru
                ]);

                // B. Simpan Data ke Tabel Pembayaran (Otomatis)
                Pembayaran::create([
                    'pelanggan_id'       => $pelanggan->pelanggan_id, // Ambil ID yang baru dibuat
                    'sumber_pemasukan'   => 'pendaftaran',            // HARDCODE: Karena ini menu daftar
                    'status_pembayaran'  => 'lunas',                  // Asumsi bayar di muka
                    'jumlah_bayar'       => $validated['jumlah_bayar'],
                    'tanggal_pembayaran' => now(),
                    'keterangan'         => $validated['keterangan'] ?? 'Biaya Pendaftaran Anggota Baru',
                ]);

                return $pelanggan;
            });

            return response()->json([
                'message' => 'Registrasi Berhasil. Data anggota dan pembayaran pendaftaran telah tersimpan.',
                'data'    => $result
            ], 201);

        } catch (\Exception $e) {
            // Jika error, batalkan semua proses (Rollback)
            return response()->json([
                'message' => 'Gagal melakukan pendaftaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * READ (Show)
     * Menampilkan detail satu anggota beserta riwayat pembayarannya
     */
    public function show($id)
    {
        // Kita gunakan with('pembayaran') agar admin bisa lihat history bayar user ini sekalian
        $pelanggan = Pelanggan::with('pembayaran')->findOrFail($id);

        return response()->json($pelanggan);
    }

    /**
     * UPDATE
     * Mengubah data profil anggota (Nama, Alamat, Status)
     * Catatan: Update ini TIDAK mengubah data pembayaran awal (secure).
     */
    public function update(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        // Validasi parsial (sometimes)
        $validated = $request->validate([
            'nama_lengkap'     => 'sometimes|required|string|max:255',
            'alamat'           => 'nullable|string',
            'nomor_telepon'    => 'nullable|string',
            // Cek unik email, kecuali punya diri sendiri
            'email'            => 'nullable|email|unique:pelanggan,email,' . $id . ',pelanggan_id',
            'status_pelanggan' => 'nullable|in:aktif,masa_tenggang,nonaktif',
        ]);

        $pelanggan->update($validated);

        return response()->json([
            'message' => 'Data profil anggota berhasil diperbarui',
            'data'    => $pelanggan
        ]);
    }

    /**
     * DELETE (Destroy)
     * Menghapus anggota. 
     * Karena di migration ada onDelete('cascade'), pembayaran terkait juga akan terhapus otomatis.
     */
    public function destroy($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);
        
        // Hapus data
        $pelanggan->delete();

        return response()->json([
            'message' => 'Anggota berhasil dihapus (beserta riwayat pembayarannya)'
        ]);
    }
}