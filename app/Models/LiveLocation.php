<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveLocation extends Model
{
    protected $fillable = [
  'user_id','latitude','longitude','accuracy_m','captured_at'
];

public function user() {
  return $this->belongsTo(User::class);
}

}
