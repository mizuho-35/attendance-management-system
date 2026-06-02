<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Work;
use App\Models\RequestDetail;

class Request extends Model
{
    use HasFactory;
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
        return $this->hasMany(RequestDetail::class);
    }
}
