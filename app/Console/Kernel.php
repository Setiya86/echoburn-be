<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Jalankan perintah setiap tanggal 1, jam 00:01 malam
        // monthly() defaultnya jalan tiap tanggal 1 jam 00:00
        $schedule->command('rekap:pelanggan-bulanan')->monthly();
        
        // ATAU: Jika ingin dijalankan di hari terakhir bulan jam 23:55
        // $schedule->command('rekap:pelanggan-bulanan')->monthlyOn(28, '23:55') 
        // ->when(function () { return now()->endOfMonth()->isToday(); });
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}