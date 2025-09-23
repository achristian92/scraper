<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    protected $guarded = ['id'];
    protected $casts   = ['payload' => 'array'];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }
}
