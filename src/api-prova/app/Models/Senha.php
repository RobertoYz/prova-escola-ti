<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Senha extends Model
{
    protected $fillable = ['codigo', 'tipo', 'status', 'emissao', 'chamada_em'];
    
    protected $casts = [
        'emissao' => 'datetime:Y-m-d\TH:i:sP',
        'chamada_em' => 'datetime:Y-m-d\TH:i:sP',
    ];
}
