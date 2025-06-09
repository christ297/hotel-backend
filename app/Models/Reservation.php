<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'chambre_id','numero_reservation','date_arrive', 'date_depart','dure_reservation'];
   
    public function chambre()
    {
        return $this->belongsTo(Chambre::class);
    }

     public function user()
    {
        return $this->belongsTo(User::class);
    }

}
