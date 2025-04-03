<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id', 'chambre_id','numero_reservation','date_reservation','date_arrive', 'date_depart','dure_reservation'];

}
