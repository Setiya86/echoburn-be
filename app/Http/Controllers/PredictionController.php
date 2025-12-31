<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Pelanggan;
use App\Models\RiwayatPelanggan;

class PredictionController extends Controller
{
    /**
     * Endpoint utama untuk mendapatkan data grafik (History + Prediksi)
     * Frontend cukup memanggil endpoint ini.
     */
    public function getPrediction()
    {
        // 1. Ambil Data Historis
        // Prioritas: Tabel Snapshot (Cepat) -> Tabel Raw (Lambat tapi realtime)
        $historyData = $this->getHistoryData();

        // 2. Cek Kecukupan Data (Cold Start Check)
        // Jika data kurang dari 5 bulan, Prophet biasanya hasilnya jelek/error.
        // Jadi kita tampilkan data simulasi saja.
        if (count($historyData) < 5) {
            return $this->generateSimulationData();
        }

        // 3. Jika Data Cukup, Lakukan Prediksi Real dengan Python
        return $this->runRealPrediction($historyData);
    }

    /**
     * Mengambil data historis:
     * - Coba dari tabel 'riwayat_pelanggan' (Snapshot bulanan)
     * - Jika kosong, hitung ulang dari tabel 'pelanggan' (Raw data)
     */
    private function getHistoryData()
    {
        // Cek apakah tabel snapshot (riwayat_pelanggan) punya data?
        // Pastikan Anda sudah membuat model RiwayatPelanggan
        $snapshots = RiwayatPelanggan::orderBy('periode_rekap', 'asc')->get();

        if ($snapshots->count() >= 5) {
            // Format data snapshot agar sesuai kebutuhan Python
            return $snapshots->map(function ($item) {
                return [
                    'date' => $item->periode_rekap,
                    'total' => $item->total_pelanggan_aktif
                ];
            })->toArray();
        }

        // Jika snapshot tidak ada/sedikit, rekonstruksi manual dari data mentah
        return $this->reconstructHistoryFromRaw();
    }

    /**
     * Rekonstruksi data historis dari tabel Pelanggan (Raw)
     * Berguna di awal implementasi saat belum ada snapshot bulanan.
     */
    private function reconstructHistoryFromRaw()
    {
        $data = [];
        // Kita hitung mundur 12 bulan ke belakang
        for ($i = 11; $i >= 0; $i--) {
            // Tanggal akhir bulan (misal: 31 Jan, 28 Feb...)
            $date = Carbon::now()->subMonths($i)->endOfMonth(); 
            
            // Logika: Hitung pelanggan yang mendaftar SEBELUM tanggal tersebut
            // DAN statusnya 'aktif'.
            $count = Pelanggan::where('created_at', '<=', $date)
                        ->where('status_pelanggan', 'aktif')
                        ->count();

            // Hanya masukkan ke array jika count > 0 (untuk membuang data bulan kosong di awal berdiri)
            if ($count > 0 || count($data) > 0) {
                 $data[] = [
                    'date' => $date->format('Y-m-d'),
                    'total' => $count
                ];
            }
        }
        return $data;
    }

    /**
     * Menjalankan script Python untuk prediksi Prophet
     */
    private function runRealPrediction($historyData)
    {
        try {
            // 1. Simpan data input ke JSON sementara
            $inputPath = storage_path('app/python/temp_input_prediction.json');
            // Pastikan folder storage/app/python ada
            if (!file_exists(dirname($inputPath))) {
                mkdir(dirname($inputPath), 0755, true);
            }
            file_put_contents($inputPath, json_encode($historyData));

            // 2. Tentukan path script Python
            // Pastikan Anda menamai file pythonnya: predict_active_users.py
            $scriptPath = storage_path('app/python/predict_active_users.py');

            // 3. Eksekusi Python
            // Gunakan 'python3' atau path lengkap (misal: /usr/bin/python3) jika di server linux
            $result = Process::run("python3 \"{$scriptPath}\" \"{$inputPath}\"");

            // 4. Handle Error Eksekusi (Misal library prophet belum install)
            if ($result->failed()) {
                Log::error("Python Prediction Failed: " . $result->errorOutput());
                // Fallback ke simulasi agar user tidak melihat error 500
                return $this->generateSimulationData();
            }

            // 5. Parse Output
            $output = json_decode($result->output(), true);
            
            // Cek error logis dari dalam script Python
            if (isset($output['error'])) {
                 Log::error("Python Logic Error: " . $output['error']);
                 return $this->generateSimulationData();
            }

            return response()->json([
                'status' => 'success',
                'mode' => 'real', // Penanda untuk Frontend
                'history' => $historyData,
                'forecast' => $output
            ]);

        } catch (\Exception $e) {
            Log::error("Prediction Controller Error: " . $e->getMessage());
            return $this->generateSimulationData();
        }
    }

    /**
     * Generate Data Dummy (Mode Simulasi)
     * Digunakan saat data asli belum cukup atau server Python error
     */
    private function generateSimulationData()
    {
        $historyArr = [];
        $forecastArr = [];
        
        $startDate = Carbon::now()->subMonths(5)->startOfMonth();
        $currentTotal = 210; // Angka awal pura-pura

        // Generate 12 bulan (6 history + 6 forecast)
        for ($i = 0; $i < 12; i++) {
            $date = $startDate->copy()->addMonths($i);
            
            // Simulasi pertumbuhan acak (Net Growth)
            $growth = rand(15, 35); // Masuk
            $churn = floor($currentTotal * (rand(3, 8) / 100)); // Keluar 3-8%
            $currentTotal = $currentTotal + $growth - $churn;

            $isForecast = $i > 5; // 6 Bulan pertama adalah history

            // Format data disamakan dengan output Real Prediction
            // Agar Frontend tidak perlu coding ulang
            if ($isForecast) {
                 $forecastArr[] = [
                    'ds' => $date->format('Y-m-d'),
                    'yhat' => $currentTotal, // Prediksi
                    'yhat_lower' => $currentTotal - 10,
                    'yhat_upper' => $currentTotal + 10
                ];
            } else {
                $historyArr[] = [
                    'date' => $date->format('Y-m-d'),
                    'total' => $currentTotal
                ];
            }
        }
        
        return response()->json([
            'status' => 'success',
            'mode' => 'simulation', // Penanda ini penting buat Frontend tahu ini data palsu
            'message' => 'Data belum cukup atau service Python tidak tersedia. Menampilkan simulasi.',
            'history' => $historyArr,
            'forecast' => $forecastArr
        ]);
    }
}