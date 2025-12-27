<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    protected $table = 'laporan';

    protected $primaryKey = 'laporan_id';

    protected $fillable = [
        'user_id',
        'aktivitas_id',
        'jenis_laporan',
        'tanggal',
        'volume_sampah_masuk',
        'volume_sampah_setelah_dibakar',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function aktivitas()
    {
        return $this->belongsTo(Aktivitas::class, 'aktivitas_id', 'aktivitas_id');
    }
}
