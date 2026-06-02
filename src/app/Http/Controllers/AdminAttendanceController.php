<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\DetailRequest;
use App\Models\Work;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\Request as RequestModel;
use App\Services\AttendanceService;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    protected $attendanceService;

    private const ROLE_STAFF = 0;
    private const STATUS_PENDING = 0;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $date = Carbon::today()->toDateString();
        $works = Work::with('user')
            ->where('work_date', $date)
            ->orderBy('user_id')
            ->get();

        return view('attendance.daily_list', [
            'works' => $works,
            'date' => $date,
            'previousDate' => Carbon::parse($date)->subDay()->toDateString(),
            'nextDate' => Carbon::parse($date)->addDay()->toDateString(),
        ]);
    }

    public function dailyList(Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $users = User::where('role', self::ROLE_STAFF)->orderBy('id')->get();
        $works = Work::where('work_date', $date)
            ->get()
            ->keyBy('user_id');

        return view('attendance.daily_list', [
            'users' => $users,
            'works' => $works,
            'date' => $date,
            'previousDate' => Carbon::parse($date)->subDay()->toDateString(),
            'nextDate' => Carbon::parse($date)->addDay()->toDateString(),
        ]);
    }

    public function detail(Request $request, $id)
    {
        $staffId = $request->query('user_id');
        $user = User::findOrFail($staffId);

        if ($id == 0) {
            $mode = 'edit';
            $date = $request->query('date');
            $work = (object)[
                'id' => 0,
                'work_date' => $date,
                'work_start' => null,
                'work_end' => null,
                'remarks' => '',
                'breakTimes' => collect(),
            ];

            $viewData = [
                'work_start' => '',
                'work_end'   => '',
                'remarks'    => '',
                'breaks'     => [
                    1 => ['start' => '', 'end' => ''],
                ],
            ];

            return view('attendance.admin_detail', compact('work', 'mode', 'viewData', 'user'));
        }

        $work = Work::with('breakTimes')->findOrFail($id);
        $pastRequestId = $request->query('request_id');
        $pendingRequest = RequestModel::where('work_id', $id)->where('status', self::STATUS_PENDING)->first();

        $latestRequest = null;

        if ($pastRequestId) {
            $mode = 'approved_log';
            $currentRequest = RequestModel::with('requestDetails')->findOrFail($pastRequestId);
            $viewData = $this->attendanceService->buildViewDataFromDetails($work, $currentRequest);

        } elseif ($pendingRequest) {
            $mode = 'pending';
            $latestRequest = $pendingRequest;
            $viewData = $this->attendanceService->buildViewDataFromDetails($work, $latestRequest);

        } else {
            $mode = 'edit';
            $breaks = [];
            foreach ($work->breakTimes as $index => $break) {
                $breaks[$index + 1] = [
                    'start' => $break->break_start ? Carbon::parse($break->break_start)->format('H:i') : '',
                    'end'   => $break->break_end ? Carbon::parse($break->break_end)->format('H:i') : '',
                ];
            }
            if (empty($breaks)) { $breaks = [1 => ['start' => '', 'end' => '']]; }

            $viewData = [
                'work_start' => $work->work_start ? Carbon::parse($work->work_start)->format('H:i') : '',
                'work_end'   => $work->work_end ? Carbon::parse($work->work_end)->format('H:i') : '',
                'remarks'    => $work->remarks,
                'breaks'     => $breaks,
            ];
        }

        return view('attendance.admin_detail', compact('work', 'mode', 'viewData', 'user', 'latestRequest'));
    }

    public function update(DetailRequest $request, $id)
    {
        if ($id == 0) {
            $work = new Work();
            $work->user_id = $request->input('user_id') ?? $request->query('user_id');
            $work->work_date = $request->input('work_date');
        } else {
            $work = Work::findOrFail($id);
        }

        $date = Carbon::parse($work->work_date)->toDateString();
        $work->work_start = $request->work_start ? $date . ' ' . $request->work_start . ':00' : null;
        $work->work_end   = $request->work_end ? $date . ' ' . $request->work_end . ':00' : null;
        $work->remarks    = $request->remarks;
        $work->save();

        if ($id != 0) {
            $work->breakTimes()->delete();
        }

        $breaksData = $request->input('breaks', []);
        foreach ($breaksData as $break) {
            $start = $break['start'] ?? null;
            $end   = $break['end'] ?? null;

            if (!empty($start) || !empty($end)) {
                BreakTime::create([
                    'work_id'     => $work->id,
                    'break_start' => $start ? $date . ' ' . $start . ':00' : null,
                    'break_end'   => $end ? $date . ' ' . $end . ':00' : null,
                ]);
            }
        }

        $work->unsetRelation('breakTimes');
        $work->load('breakTimes');

        $this->attendanceService->recalculateTotals($work);

        $work->break_total = $work->break_total;
        $work->work_total  = $work->work_total;
        $work->save();

        $staffId = $work->user_id;
        return redirect('/admin/attendance/' . $work->id . '?date=' . $date . '&user_id=' . $staffId)
            ->with('success', '勤怠データを修正しました');
    }

    public function staffMonthlyList($id)
    {
        $user = User::findOrFail($id);
        $currentMonth = request('month', now()->format('Y-m'));

        $startOfMonth = Carbon::parse($currentMonth)->startOfMonth();
        $endOfMonth   = Carbon::parse($currentMonth)->endOfMonth();

        $dates = [];
        $startDate = $startOfMonth->copy();
        while ($startDate->lte($endOfMonth)) {
            $dates[] = $startDate->copy();
            $startDate->addDay();
        }

        $worksData = Work::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date')
            ->get();

        $works = [];
        foreach ($worksData as $work) {
            $dateStr = Carbon::parse($work->work_date)->format('Y-m-d');
            $works[$dateStr] = $work;
        }

        $previousMonth = Carbon::parse($currentMonth)->subMonth()->format('Y-m');
        $nextMonth = Carbon::parse($currentMonth)->addMonth()->format('Y-m');

        return view('attendance.monthly_list', [
            'dates' => $dates,
            'works' => $works,
            'currentMonth' => $currentMonth,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
            'user' => $user,
        ]);
    }
}