<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatPelanggan extends Model
{
    use HasFactory;
    
    protected $table = 'riwayat_pelanggan';
    
    protected $fillable = [
        'periode_rekap', 
        'total_pelanggan_aktif',
        'jumlah_baru_bulan_ini',
        'jumlah_keluar_bulan_ini'
    ];
}