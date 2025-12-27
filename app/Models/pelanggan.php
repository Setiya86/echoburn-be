<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    use HasFactory; // Tambahkan Trait HasFactory jika digunakan untuk seeding/testing

    protected $table = 'pelanggan';

    protected $primaryKey = 'pelanggan_id';

    // 1. Fillable: Sesuaikan dengan kolom Migration terbaru
    protected $fillable = [
        'nama_lengkap',
        'alamat',
        'nomor_telepon',
        'email',
        // 'level_pelanggan' dihapus karena tidak ada di Migration
        'status_pelanggan',
        'jumlah_sampah_sudah_dibakar',
    ];

    // 2. Accessors & Appends: Tambahkan field virtual untuk Frontend
    protected $appends = [
        'id', 
        'nama', 
        'telepon', 
        'totalSampah', 
        'status', 
        'pembayaran'
    ];

    // --- RELATIONS ---

    // Relasi ke tabel Pembayaran (Digunakan untuk menghitung status pembayaran terbaru)
    public function pembayaran()
    {
        // Pastikan nama model Pembayaran benar
        return $this->hasMany(Pembayaran::class, 'pelanggan_id', 'pelanggan_id');
    }

    // Relasi ke tabel Aktivitas (Tetap dipertahankan)
    public function aktivitas()
    {
        // Pastikan nama model Aktivitas benar
        return $this->hasMany(Aktivitas::class, 'pelanggan_id', 'pelanggan_id');
    }

    // --- ACCESSORS (Getters) ---
    
    // Mengubah 'pelanggan_id' menjadi 'id'
    public function getIdAttribute()
    {
        return $this->attributes['pelanggan_id'];
    }

    // Mengubah 'nama_lengkap' menjadi 'nama'
    public function getNamaAttribute()
    {
        return $this->attributes['nama_lengkap'];
    }

    // Mengubah 'nomor_telepon' menjadi 'telepon'
    public function getTeleponAttribute()
    {
        return $this->attributes['nomor_telepon'];
    }
    
    // Mengubah 'jumlah_sampah_sudah_dibakar' menjadi 'totalSampah'
    public function getTotalSampahAttribute()
    {
        return $this->attributes['jumlah_sampah_sudah_dibakar'];
    }
    
    // Mengubah 'status_pelanggan' menjadi 'status'
    public function getStatusAttribute()
    {
        return $this->attributes['status_pelanggan'];
    }

    // Menghitung Status Pembayaran Terakhir
    public function getPembayaranAttribute()
    {
        // Memuat relasi pembayaran, mengambil yang terbaru
        $latestPayment = $this->pembayaran()->latest('created_at')->first();

        // Jika tidak ada transaksi atau transaksi terakhir adalah 'pending'/'batal'
        if (!$latestPayment || $latestPayment->status_pembayaran !== 'lunas') {
            return 'pending'; 
        }

        // Jika transaksi terakhir adalah 'lunas'
        return 'lunas'; 
    }
}