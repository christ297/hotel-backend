<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chambre extends Model
{   use HasFactory;
    
    protected $fillable = ['numero_chambre', 'type', 'prix_nuite', 'disponibilite','photo'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

}
