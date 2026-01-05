<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    protected $fillable = [
        'user_id','captured_at','latitude','longitude','accuracy_m','address','notes'
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy_m' => 'float',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function photos() { return $this->hasMany(SalesReportPhoto::class); }
}

