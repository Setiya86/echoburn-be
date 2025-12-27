<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembakaran extends Model
{
    protected $table = 'pembakaran';

    protected $primaryKey = 'pembakaran_id';

    protected $fillable = [
        'aktivitas_id',
        'jenis',
        'total_biaya',
        'jadwal_langganan',
    ];

    public function aktivitas()
    {
        return $this->belongsTo(Aktivitas::class, 'aktivitas_id', 'aktivitas_id');
    }
}
