<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $today = now()->toDateString();
        $work = Work::with('breakTimes')
            ->where('user_id', Auth::id())
            ->where('work_date', $today)
            ->first();

        $status = 'before_work';
        if ($work) {
            if ($work->work_end) {
                $status = 'after_work';
            } elseif ($work->breakTimes()->whereNull('break_end')->exists()) {
                $status = 'on_break';
            } elseif ($work->work_start) {
                $status = 'working';
            }
        }

        return view('attendance.index', compact('status', 'work'));
    }

    public function startWork()
    {
        $today = today();
        $userId = Auth::id();

        $exists = Work::where('user_id', $userId)->where('work_date', $today)->exists();
        if (!$exists) {
            Work::create([
                'user_id' => $userId,
                'work_date' => $today,
                'work_start' => now(),
            ]);
        }

        return redirect('/attendance');
    }

    public function endWork()
    {
        $work = Work::where('user_id', Auth::id())->where('work_date', today())->first();

        if ($work && !$work->work_end) {
            $work->update(['work_end' => now()]);
            $work->refresh();
            $this->attendanceService->recalculateTotals($work);
            $work->save();
        }

        return redirect('/attendance');
    }

    public function breakStart()
    {
        $work = Work::where('user_id', Auth::id())->where('work_date', today())->first();

        if ($work && !$work->breakTimes()->whereNull('break_end')->exists()) {
            $work->breakTimes()->create(['break_start' => now()]);
        }

        return redirect('/attendance');
    }

    public function breakEnd()
    {
        $work = Work::where('user_id', Auth::id())->where('work_date', today())->first();

        if ($work) {
            $break = $work->breakTimes()->whereNull('break_end')->first();
            if ($break) {
                $break->update(['break_end' => now()]);
                $work->unsetRelation('breakTimes')->load('breakTimes');
                $this->attendanceService->recalculateTotals($work);
                $work->save();
            }
        }

        return redirect('/attendance');
    }

    public function monthlyList(Request $request)
    {
        $userId = Auth::id();
        $month = $request->month ?? now()->format('Y-m');

        $monthlyData = $this->attendanceService->getMonthlyListData($userId, $month);

        return view(
            'attendance.monthly_list',
            array_merge(
                [
                    'month' => $month,
                    'currentMonth' => $month,
                    'user' => null,
                ],
                $monthlyData
            )
        );
    }

    public function detail(Request $request, $id)
    {
        $user = Auth::user();
        $date = $request->query('date');
        $pastRequestId = $request->query('request_id');

        $attendanceData = $this->attendanceService->getAdminDetailData($id, $date, $pastRequestId);

        return view(
            'attendance.detail',
            array_merge(['user' => $user], $attendanceData)
        );
    }
}