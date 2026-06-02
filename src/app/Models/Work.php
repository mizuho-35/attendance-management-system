<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\Request;

class Work extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'work_start',
        'work_end',
        'break_total',
        'work_total',
    ];

    protected $casts = [
        'work_start' => 'datetime',
        'work_end' => 'datetime',
        'break_total' => 'string',
        'work_total'  => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }
}
