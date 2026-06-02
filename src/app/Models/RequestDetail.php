<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Request;
use App\Models\BreakTime;

class RequestDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'request_id',
        'break_time_id',
        'type',
        'value_datetime',
        'value_int',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }
}
