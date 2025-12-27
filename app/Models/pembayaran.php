<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    // 1. Nama Tabel
    protected $table = 'pembayaran';

    // 2. Primary Key
    protected $primaryKey = 'pembayaran_id';

    // 3. Kolom yang boleh diisi
    protected $fillable = [
        'pelanggan_id',
        'sumber_pemasukan',
        'status_pembayaran',
        'jumlah_bayar',
        'tanggal_pembayaran',
        'keterangan'
    ];

    // 4. Casting tipe data (agar format uang dan tanggal benar)
    protected $casts = [
        'jumlah_bayar'       => 'decimal:2',
        'tanggal_pembayaran' => 'datetime',
    ];

    // 5. Relasi ke Pelanggan
    public function pelanggan()
    {
        // Parameter ke-2: foreign key di tabel ini
        // Parameter ke-3: primary key di tabel pelanggan
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id', 'pelanggan_id');
    }
}