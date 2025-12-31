<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pelanggan;
use App\Models\RiwayatPelanggan;
use Carbon\Carbon;

class RekapPelangganBulanan extends Command
{
    // Nama perintah yang nanti diketik di terminal
    protected $signature = 'rekap:pelanggan-bulanan';

    // Deskripsi perintah
    protected $description = 'Menghitung dan menyimpan snapshot pelanggan aktif bulan ini';

    public function handle()
    {
        $bulanIni = Carbon::now();
        
        // 1. Hitung Total Aktif SAAT INI
        $totalAktif = Pelanggan::where('status_pelanggan', 'aktif')->count();
        
        // 2. (Opsional) Hitung yang baru daftar bulan ini
        $baruBulanIni = Pelanggan::whereMonth('created_at', $bulanIni->month)
                                 ->whereYear('created_at', $bulanIni->year)
                                 ->count();

        // 3. Simpan ke tabel riwayat
        // updateOrCreate berguna agar jika script jalan 2x, datanya tidak dobel (malah terupdate)
        RiwayatPelanggan::updateOrCreate(
            [
                // Cek apakah sudah ada data untuk bulan ini?
                'periode_rekap' => $bulanIni->startOfMonth()->format('Y-m-d') 
            ],
            [
                'total_pelanggan_aktif' => $totalAktif,
                'jumlah_baru_bulan_ini' => $baruBulanIni,
                // 'jumlah_keluar' => butuh logika tambahan di tabel pelanggan untuk melacak kapan dia nonaktif
            ]
        );

        $this->info("Rekap bulan {$bulanIni->format('F Y')} berhasil disimpan! Total: {$totalAktif}");
    }
}