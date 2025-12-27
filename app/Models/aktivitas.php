<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aktivitas extends Model
{
    protected $table = 'aktivitas';

    protected $primaryKey = 'aktivitas_id';

    protected $fillable = [
        'user_id',
        'pelanggan_id',
        'tobong_id',
        'jumlah_kg',
        'status_proses',
        'waktu_pencatatan',
    ];

    // Tambahkan field virtual (Accessors) agar muncul di response JSON
    protected $appends = [
        'id', 
        'namaAnggota', 
        'jenisPembakaran', 
        'beratSampah', 
        'namaTobong', 
        'tanggal', 
        'jumlahUang', 
        'statusProses'
    ];

    // --- RELATIONS ---

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id', 'pelanggan_id');
    }

    public function tobong()
    {
        return $this->belongsTo(Tobong::class, 'tobong_id', 'tobong_id');
    }

    public function pembakaran()
    {
        return $this->hasOne(Pembakaran::class, 'aktivitas_id', 'aktivitas_id');
    }

    // --- ACCESSORS (MAPPING KE FORMAT FE) ---

    // Mengubah 'aktivitas_id' menjadi 'id'
    public function getIdAttribute(): int
    {
        return $this->attributes['aktivitas_id'];
    }

    // Mengambil nama anggota/pembakar
    public function getNamaAnggotaAttribute(): string
    {
        // Jika ada pelanggan terdaftar, ambil namanya
        if ($this->pelanggan) {
            return $this->pelanggan->nama_lengkap;
        }
        // Jika tidak ada pelanggan (misal: sekali bakar), ambil dari kolom nama sementara jika ada, atau default.
        // ASUMSI: Karena FE mengirim namaAnggota untuk mode 'sekali' (non-member),
        // Anda perlu kolom temporary_name di tabel Aktivitas untuk non-member.
        // Karena tidak ada kolom temporary_name, kita kembalikan string default.
        return 'Umum (Sekali Bakar)'; 
    }
    
    // Mengubah 'jenis' (dari Pembakaran) menjadi 'jenisPembakaran'
    public function getJenisPembakaranAttribute(): string
    {
        // Mapping: 'tempat' -> 'sekali', 'langganan' -> 'langganan'
        if ($this->pembakaran) {
            return $this->pembakaran->jenis == 'tempat' ? 'sekali' : 'langganan';
        }
        return 'langganan'; // Default
    }

    // Mengubah 'jumlah_kg' menjadi 'beratSampah'
    public function getBeratSampahAttribute(): float
    {
        return $this->attributes['jumlah_kg'];
    }

    // Mengambil nama tobong
    public function getNamaTobongAttribute(): string
    {
        return $this->tobong->nama_tobong ?? 'Tobong Tidak Ditemukan';
    }
    
    // Mengubah 'status_proses'
    public function getStatusProsesAttribute(): string
    {
        return $this->attributes['status_proses'];
    }
    
    // Mengambil tanggal (waktu_pencatatan)
    public function getTanggalAttribute(): string
    {
        // Mengembalikan format tanggal YYYY-MM-DD
        return $this->waktu_pencatatan ? substr($this->waktu_pencatatan, 0, 10) : now()->toDateString();
    }
    
    // Mengambil 'total_biaya' (dari Pembakaran) menjadi 'jumlahUang'
    public function getJumlahUangAttribute(): ?int
    {
        if ($this->pembakaran && $this->pembakaran->total_biaya) {
            return $this->pembakaran->total_biaya;
        }
        return null;
    }
}