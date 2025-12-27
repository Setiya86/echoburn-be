<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Opsional: Tambahkan jika pakai Factory
use Illuminate\Database\Eloquent\Model;

class Tobong extends Model
{
    use HasFactory;

    // 1. Nama Tabel
    protected $table = 'tobong';

    // 2. Primary Key
    protected $primaryKey = 'tobong_id';

    // 3. Kolom yang boleh diisi (Mass Assignment)
    // Sesuai dengan Migration terbaru
    protected $fillable = [
        'nama_tobong',         // Baru
        'lokasi',
        'tanggal_pembuatan',   // Menggantikan tahun_pembuatan
        'kapasitas',           // Menggantikan kapasitas_pembakaran
        'kapasitas_abu',
        'status_operasional',
    ];

    // 4. Casting (Opsional tapi direkomendasikan)
    // Agar 'tanggal_pembuatan' otomatis jadi objek Carbon (bisa diformat tgl/bln/thn)
    protected $casts = [
        'tanggal_pembuatan' => 'date',
    ];

    // 5. Relasi (Jika ada tabel aktivitas)
    public function aktivitas()
    {
        return $this->hasMany(Aktivitas::class, 'tobong_id', 'tobong_id');
    }
    
    // Relasi ke Riwayat Perawatan (Sesuai diskusi sebelumnya)
    public function riwayatPerawatan()
    {
        return $this->hasMany(RiwayatPerawatan::class, 'tobong_id', 'tobong_id');
    }
}