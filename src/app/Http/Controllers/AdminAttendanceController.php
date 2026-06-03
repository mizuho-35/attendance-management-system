<?php

namespace App\Http\Controllers;

use App\Exports\StaffAttendanceExport;
use App\Http\Requests\DetailRequest;
use App\Models\User;
use App\Models\Work;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function dailyList(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        return view('attendance.daily_list', [
            'users' => User::onlyStaff()->get(),
            'works' => Work::where('work_date', $date)->get()->keyBy('user_id'),
            'date' => $date,
            'previousDate' => $this->attendanceService->getAdjacentDate($date, -1),
            'nextDate' => $this->attendanceService->getAdjacentDate($date, 1),
        ]);
    }

    public function detail(Request $request, $id)
    {
        $user = User::findOrFail($request->query('user_id'));
        $date = $request->query('date');
        $pastRequestId = $request->query('request_id');

        $attendanceData = $this->attendanceService->getAdminDetailData($id, $date, $pastRequestId);

        return view(
            'attendance.admin_detail',
            array_merge(['user' => $user], $attendanceData)
        );
    }

    public function update(DetailRequest $request, $id)
    {
        $work = Work::updateOrCreateWork($id, $request->validatedWithUser());

        $this->attendanceService->updateBreakTimes($work, $request->input('breaks', []));
        $this->attendanceService->recalculateTotals($work);
        $work->save();

        return redirect('/admin/attendance/' . $work->id . "?date={$work->work_date}&user_id={$work->user_id}")
            ->with('success', '勤怠データを修正しました');
    }

    public function staffMonthlyList($id)
    {
        $user = User::findOrFail($id);
        $currentMonth = request('month', now()->format('Y-m'));

        $uncalculatedWorks = Work::where('user_id', $user->id)
            ->whereBetween('work_date', [
                \Carbon\Carbon::parse($currentMonth)->startOfMonth(),
                \Carbon\Carbon::parse($currentMonth)->endOfMonth(),
            ])
            ->where(function ($query) {
                $query->whereNull('work_total')->orWhereNull('break_total');
            })
            ->get();

        foreach ($uncalculatedWorks as $w) {
            if ($w->work_start && $w->work_end) {
                $this->attendanceService->recalculateTotals($w);
                $w->save();
            }
        }

        return view(
            'attendance.monthly_list',
            array_merge(
                [
                    'user' => $user,
                    'currentMonth' => $currentMonth,
                ],
                $this->attendanceService->getMonthlyListData($user->id, $currentMonth)
            )
        );
    }

    public function export(Request $request)
    {
        $userId = $request->input('id');
        $month = $request->input('month');

        if (!$userId || !$month) {
            return back()->with('error', 'パラメータが足りません。CSVを出力できませんでした。');
        }

        if (!Work::hasWorkForMonth($userId, $month)) {
            return back()->with('error', '指定された月の勤怠データが存在しないため、出力できません。');
        }

        $exporter = new StaffAttendanceExport();

        return $exporter->download((int) $userId, (string) $month);
    }
}