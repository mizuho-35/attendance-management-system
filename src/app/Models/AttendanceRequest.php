<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Work;

class AttendanceRequest extends Model
{
    use HasFactory;
    protected $table = 'requests';

    protected $fillable = [
        'user_id',
        'work_id',
        'status',
        'remarks',
        'approved_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function requestDetails()
    {
        return $this->hasMany(\App\Models\AttendanceRequestDetail::class, 'request_id');
    }
}
