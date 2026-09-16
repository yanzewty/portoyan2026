<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

   
    protected $fillable = [
        'profile_id',
        'nama_organisasi',
        'posisi',
        'periode',
        'deskripsi_pekerjaan'
    ];

 
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}