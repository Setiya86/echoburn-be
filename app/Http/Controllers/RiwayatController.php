<?php

namespace App\Http\Controllers;

use App\Models\Aktivitas;
use App\Models\Pembakaran;
use App\Models\Pelanggan; 
use App\Models\Tobong;
use App\Models\Pembayaran; // Diperlukan untuk mencatat keuangan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiwayatController extends Controller
{
    // Relasi yang wajib dimuat untuk accessor FE
    private $eagerLoads = ['pelanggan', 'tobong', 'pembakaran'];
    
    /**
     * READ (Index) - Menampilkan daftar semua aktivitas pembakaran.
     */
    public function index()
    {
        $data = Aktivitas::with($this->eagerLoads) 
                         ->where('user_id', auth()->id())
                         ->orderBy('created_at', 'desc')
                         ->get();

        return response()->json($data);
    }

    /**
     * CREATE (Store) - Mencatat Aktivitas, Pembakaran, Update Saldo Sampah, DAN Mencatat Keuangan.
     */
    public function store(Request $request)
    {
        // Mapping jenis FE ke BE: 'sekali' -> 'tempat', 'langganan' -> 'langganan'
        $jenis_pembakaran_be = $request->jenisPembakaran == 'sekali' ? 'tempat' : 'langganan';
        
        // 1. Cari Tobong (Wajib)
        $tobong = Tobong::where('nama_tobong', $request->namaTobong)->first();
        if (!$tobong) {
             return response()->json(['message' => "Tobong '{$request->namaTobong}' tidak ditemukan."], 404);
        }

        // 2. Cari Pelanggan (Hanya jika langganan)
        $pelangganId = null;
        $pelanggan = null;
        if ($request->jenisPembakaran == 'langganan') {
            $pelanggan = Pelanggan::where('nama_lengkap', $request->namaAnggota)->first();
            $pelangganId = $pelanggan->pelanggan_id ?? null;
            if (!$pelanggan) {
                return response()->json(['message' => "Anggota langganan dengan nama '{$request->namaAnggota}' tidak ditemukan."], 404);
            }
        }
        
        // 3. Validasi Input (menggunakan nama field FE)
        $request->validate([
            'beratSampah'    => 'required|numeric|min:0.01',
            'statusProses'   => 'nullable|in:pending,proses,selesai',
            'jumlahUang'     => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // A. Simpan Aktivitas
            $aktivitas = Aktivitas::create([
                'user_id'          => auth()->id(),
                'tobong_id'        => $tobong->tobong_id,
                'pelanggan_id'     => $pelangganId, 
                'jumlah_kg'        => $request->beratSampah, // Data sampah
                'status_proses'    => $request->statusProses ?? 'pending',
                'waktu_pencatatan' => now(), 
            ]);

            // B. Simpan Pembakaran (Detail Jenis & Biaya)
            Pembakaran::create([
                'aktivitas_id'     => $aktivitas->aktivitas_id,
                'jenis'            => $jenis_pembakaran_be, 
                'total_biaya'      => $request->jumlahUang,  
                'jadwal_langganan' => null,
            ]);

            // C. UPDATE PELANGGAN (Increment Jumlah Sampah)
            if ($pelanggan) {
                $pelanggan->increment('jumlah_sampah_sudah_dibakar', $request->beratSampah);
            }
            
            // D. MENCATAT KEUANGAN (Hanya jika Sekali Bakar / Non-Anggota)
            if ($jenis_pembakaran_be == 'tempat' && $request->jumlahUang > 0) {
                Pembayaran::create([
                    'pelanggan_id'       => null, // Diizinkan NULL oleh migrasi yang diperbaiki
                    'sumber_pemasukan'   => 'ditempat', 
                    'status_pembayaran'  => 'lunas', 
                    'jumlah_bayar'       => $request->jumlahUang,
                    'tanggal_pembayaran' => Carbon::now(),
                    'keterangan'         => 'Pembayaran sekali bakar sampah (non-anggota: ' . $request->namaAnggota . ')',
                ]);
            }
            
            DB::commit();

            return response()->json([
                'message' => 'Data berhasil disimpan.',
                'data'    => $aktivitas->load($this->eagerLoads) 
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan data (Server Error)', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * READ (Show) - Menampilkan detail satu aktivitas.
     */
    public function show($id)
    {
        $data = Aktivitas::with($this->eagerLoads)
                         ->where('user_id', auth()->id())
                         ->where('aktivitas_id', $id)
                         ->firstOrFail();

        return response()->json($data);
    }

    /**
     * UPDATE - Mengubah data aktivitas dan memperbarui saldo sampah.
     */
    public function update(Request $request, $id)
    {
        $aktivitas = Aktivitas::with($this->eagerLoads)
                            ->where('user_id', auth()->id())
                            ->where('aktivitas_id', $id)
                            ->firstOrFail();

        // Mapping input FE
        $tobongId = $request->namaTobong ? (Tobong::where('nama_tobong', $request->namaTobong)->first()->tobong_id ?? $aktivitas->tobong_id) : $aktivitas->tobong_id;
        $jenisBe = $request->jenisPembakaran ? ($request->jenisPembakaran == 'sekali' ? 'tempat' : 'langganan') : $aktivitas->pembakaran->jenis;
        $jumlahUang = ($request->jenisPembakaran == 'sekali' && $request->jumlahUang) ? $request->jumlahUang : null;
        
        // Validasi Update
        $request->validate([
            'beratSampah'    => 'nullable|numeric|min:0',
            'statusProses'   => 'nullable|in:pending,proses,selesai',
            'jumlahUang'     => 'nullable|numeric|min:0',
        ]);

        // Data lama untuk perhitungan saldo sampah
        $oldJumlahKg = $aktivitas->jumlah_kg;

        DB::beginTransaction();

        try {
            // Update Aktivitas
            $aktivitas->update([
                'tobong_id'     => $tobongId,
                'jumlah_kg'     => $request->beratSampah ?? $aktivitas->jumlah_kg,
                'status_proses' => $request->statusProses ?? $aktivitas->status_proses,
            ]);

            // Update Pembakaran
            $aktivitas->pembakaran()->updateOrCreate(
                ['aktivitas_id' => $aktivitas->aktivitas_id],
                [
                    'jenis'       => $jenisBe,
                    'total_biaya' => $jumlahUang,
                ]
            );

            // Perhitungan Saldo Sampah
            if ($aktivitas->pelanggan_id) {
                $selisih = $aktivitas->jumlah_kg - $oldJumlahKg;
                if ($selisih != 0) {
                    Pelanggan::find($aktivitas->pelanggan_id)
                        ->increment('jumlah_sampah_sudah_dibakar', $selisih); 
                }
            }
            
            DB::commit();

            return response()->json([
                'message' => 'Data berhasil diperbarui',
                'data'    => $aktivitas->fresh()->load($this->eagerLoads)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal update', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE (Destroy) - Menghapus aktivitas dan mengurangi saldo sampah.
     */
    public function destroy($id)
    {
        $aktivitas = Aktivitas::with($this->eagerLoads)
                            ->where('user_id', auth()->id())
                            ->where('aktivitas_id', $id)
                            ->firstOrFail();

        DB::beginTransaction();
        try {
            // LOGIKA HAPUS: Kurangi saldo pelanggan
            if ($aktivitas->pelanggan_id) {
                Pelanggan::find($aktivitas->pelanggan_id)
                    ->decrement('jumlah_sampah_sudah_dibakar', $aktivitas->jumlah_kg);
            }

            $aktivitas->delete(); // Pembakaran ikut terhapus karena foreign key cascade
            
            DB::commit();
            return response()->json(['message' => 'Data berhasil dihapus']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus', 'error' => $e->getMessage()], 500);
        }
    }
}