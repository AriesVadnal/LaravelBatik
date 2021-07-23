<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PesananDetail extends Model
{
    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class);
    }
}
