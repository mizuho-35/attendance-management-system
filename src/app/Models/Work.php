<?php

namespace App\Models;

use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'work_total' => 'string',
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

    public static function hasWorkForMonth(int $userId, string $month): bool
    {
        return self::where('user_id', $userId)
            ->whereBetween('work_date', [
                Carbon::parse($month)->startOfMonth(),
                Carbon::parse($month)->endOfMonth(),
            ])->exists();
    }

    public static function updateOrCreateWork(int $id, array $data): self
    {
        $work = ($id === 0) ? new self() : self::findOrFail($id);

        $work->user_id = $data['user_id'];
        $work->work_date = $data['work_date'];
        $work->work_start = $data['work_start'];
        $work->work_end = $data['work_end'];
        $work->remarks = $data['remarks'];
        $work->save();

        return $work;
    }
}