<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Work;
use App\Models\RequestDetail;

class BreakTime extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end'   => 'datetime',
    ];

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function requestDetails()
    {
        return $this->hasMany(RequestDetail::class);
    }
}
