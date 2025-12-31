<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Import Model dengan Benar
use App\Models\Pelanggan;
use App\Models\Aktivitas;
use App\Models\Tobong;
use App\Models\Pembayaran;
use App\Models\Pembakaran;

class DashboardController extends Controller
{
    /**
     * ADMIN DASHBOARD (Butuh Login)
     * Menampilkan statistik lengkap, keuangan, dan aktivitas.
     */
    public function index()
    {
        // 1. STATS CARDS
        // -----------------------
        
        // A. Pelanggan Aktif
        $totalPelanggan = Pelanggan::where('status_pelanggan', 'aktif')->count();
        $prevPelanggan = Pelanggan::where('status_pelanggan', 'aktif')
            ->where('created_at', '<', now()->startOfMonth())
            ->count();
        $pelangganChange = $this->calculateChange($totalPelanggan, $prevPelanggan);

        // B. Total Sampah (Sum Jumlah KG)
        $totalSampah = Aktivitas::sum('jumlah_kg');
        $sampahThisMonth = Aktivitas::whereMonth('waktu_pencatatan', now()->month)->sum('jumlah_kg');
        $sampahLastMonth = Aktivitas::whereMonth('waktu_pencatatan', now()->subMonth()->month)->sum('jumlah_kg');
        $sampahChange = $this->calculateChange($sampahThisMonth, $sampahLastMonth);

        // C. Total Keuangan (Pembakaran + Pembayaran)
        $totalBiayaPembakaran = Pembakaran::sum('total_biaya');
        $totalBayarPembayaran = Pembayaran::where('status_pembayaran', 'lunas')->sum('jumlah_bayar');
        $totalKeuangan = $totalBiayaPembakaran + $totalBayarPembayaran;
        
        // Trend Keuangan (Sederhana)
        $keuanganChange = ['label' => '+0%', 'trend' => 'neutral'];

        // D. Tobong
        $totalTobong = Tobong::count();
        $activeTobong = Tobong::where('status_operasional', 'aktif')->count();
        $prevTobong = Tobong::where('created_at', '<', now()->startOfMonth())->count();
        $tobongChange = $totalTobong - $prevTobong;

        $statsCards = [
            [
                'label' => 'Pelanggan Aktif',
                'value' => (string) $totalPelanggan,
                'change' => $pelangganChange['label'],
                'trend' => $pelangganChange['trend']
            ],
            [
                'label' => 'Total Sampah Dibakar',
                'value' => number_format($totalSampah, 0, ',', '.') . ' Kg',
                'change' => $sampahChange['label'],
                'trend' => $sampahChange['trend']
            ],
            [
                'label' => 'Total Pemasukan',
                'value' => 'Rp ' . number_format($totalKeuangan, 0, ',', '.'),
                'change' => $keuanganChange['label'],
                'trend' => $keuanganChange['trend']
            ],
            [
                'label' => 'Total Tobong',
                'value' => "{$activeTobong} / {$totalTobong}",
                'change' => ($tobongChange >= 0 ? '+' : '') . $tobongChange,
                'trend' => $tobongChange >= 0 ? 'up' : 'neutral'
            ]
        ];

        // 2. PIE CHART (Status Pembakaran)
        // -----------------------
        $statusCounts = Aktivitas::select('status_proses', DB::raw('count(*) as total'))
            ->groupBy('status_proses')
            ->pluck('total', 'status_proses')
            ->toArray();

        $statusData = [
            ['name' => 'Selesai', 'value' => $statusCounts['selesai'] ?? 0, 'color' => '#4C9876'],
            ['name' => 'Proses', 'value' => $statusCounts['proses'] ?? 0, 'color' => '#FFA500'],
            ['name' => 'Pending', 'value' => $statusCounts['pending'] ?? 0, 'color' => '#FFD700'],
        ];

        // 3. BAR CHART (Financial History 6 Bulan)
        // -----------------------
        $financialData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            
            // Pendaftaran
            $pendaftaran = Pembayaran::whereMonth('tanggal_pembayaran', $date->month)
                ->whereYear('tanggal_pembayaran', $date->year)
                ->where('sumber_pemasukan', 'pendaftaran')
                ->where('status_pembayaran', 'lunas')
                ->sum('jumlah_bayar');

            // Lainnya (Perpanjang + Ditempat + Biaya Bakar)
            $bayarLain = Pembayaran::whereMonth('tanggal_pembayaran', $date->month)
                ->whereYear('tanggal_pembayaran', $date->year)
                ->where('sumber_pemasukan', '<>', 'pendaftaran')
                ->where('status_pembayaran', 'lunas')
                ->sum('jumlah_bayar');
            
            $biayaBakar = Pembakaran::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('total_biaya');

            $financialData[] = [
                'bulan' => $date->translatedFormat('M'),
                'pendaftaran' => $pendaftaran,
                'perpanjang' => $bayarLain + $biayaBakar
            ];
        }

        // 4. RECENT ACTIVITIES
        // -----------------------
        $recentActivities = $this->getRecentActivities();

        return response()->json([
            'statsCards' => $statsCards,
            'statusData' => $statusData,
            'financialData' => $financialData,
            'recentActivities' => $recentActivities
        ]);
    }

    /**
     * PUBLIC ENDPOINT (Tanpa Login)
     * Digunakan untuk Dashboard Konsumen (Halaman Depan)
     */
    public function publicStats()
    {
        try {
            // Hitung Data Sederhana
            $totalSampah = Aktivitas::sum('jumlah_kg');
            $totalPelanggan = Pelanggan::where('status_pelanggan', 'aktif')->count();
            // Ambil hanya angka tobong aktif saja
            $activeTobong = Tobong::where('status_operasional', 'aktif')->count(); 

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_sampah' => number_format($totalSampah, 0, ',', '.') . ' Kg',
                    'pengguna_aktif' => (string) $totalPelanggan,
                    'tobong_aktif' => (string) $activeTobong, // Hanya kirim angka aktif
                    'pengurangan_emisi' => '98%' // Statis
                ]
            ]);

        } catch (\Exception $e) {
            // Return 0 jika terjadi error database agar FE tidak crash
            return response()->json([
                'status' => 'error',
                'data' => [
                    'total_sampah' => '0 Kg',
                    'pengguna_aktif' => '0',
                    'tobong_aktif' => '0',
                    'pengurangan_emisi' => '98%'
                ]
            ], 200);
        }
    }

    // --- HELPER METHODS ---

    private function calculateChange($current, $previous)
    {
        if ($previous == 0) {
            return [
                'label' => $current > 0 ? '+100%' : '0%',
                'trend' => $current > 0 ? 'up' : 'neutral'
            ];
        }
        $diff = $current - $previous;
        $percentage = ($diff / $previous) * 100;
        
        return [
            'label' => ($percentage > 0 ? '+' : '') . round($percentage, 1) . '%',
            'trend' => $percentage >= 0 ? 'up' : 'down'
        ];
    }

    private function getRecentActivities()
    {
        // Pelanggan Baru
        $newMembers = Pelanggan::latest()->limit(3)->get()->map(function($item){
            return [
                'type' => 'user',
                'message' => 'Anggota baru: ' . $item->nama_lengkap,
                'time' => $item->created_at,
                'ts' => $item->created_at->timestamp
            ];
        });

        // Pembayaran
        $payments = Pembayaran::where('status_pembayaran', 'lunas')->latest('tanggal_pembayaran')->limit(3)->get()->map(function($item){
            return [
                'type' => 'payment',
                'message' => 'Pembayaran: Rp ' . number_format($item->jumlah_bayar, 0, ',', '.'),
                'time' => $item->tanggal_pembayaran,
                'ts' => \Carbon\Carbon::parse($item->tanggal_pembayaran)->timestamp
            ];
        });

        // Pembakaran (Gunakan optional() agar aman jika tobong terhapus)
        $burns = Aktivitas::with('tobong')->latest('waktu_pencatatan')->limit(3)->get()->map(function($item){
            $namaTobong = optional($item->tobong)->nama_tobong ?? 'Tobong';
            return [
                'type' => 'burn',
                'message' => 'Membakar ' . $item->jumlah_kg . 'kg di ' . $namaTobong,
                'time' => $item->waktu_pencatatan,
                'ts' => \Carbon\Carbon::parse($item->waktu_pencatatan)->timestamp
            ];
        });

        return $newMembers->merge($payments)->merge($burns)
            ->sortByDesc('ts')
            ->take(5)
            ->values()
            ->map(function($item){
                return [
                    'type' => $item['type'],
                    'message' => $item['message'],
                    'time' => \Carbon\Carbon::parse($item['time'])->diffForHumans()
                ];
            });
    }
}