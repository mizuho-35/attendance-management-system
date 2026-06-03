<?php

namespace App\Models;

use App\Models\AttendanceRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequestDetail extends Model
{
    use HasFactory;

    protected $table = 'request_details';

    protected $fillable = [
        'request_id',
        'break_time_id',
        'type',
        'value_datetime',
        'value_int',
    ];

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class, 'request_id');
    }

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }
}