<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['employee_id', 'date', 'start_time', 'end_time'];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];
}
