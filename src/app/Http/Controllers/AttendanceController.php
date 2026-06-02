<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\DetailRequest;
use App\Models\Work;
use App\Services\AttendanceService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $attendanceService;

    private const NEW_WORK_ID = 0;
    private const STATUS_PENDING = 0;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index()
    {
        $today = Carbon::today()->toDateString();

        $work = Work::with('breakTimes')
            ->where('user_id', auth()->id())
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

        $work = Work::where('user_id', auth()->id())
            ->where('work_date', $today)
            ->first();

        if ($work) {
            return redirect('/attendance');
        }

        Work::create([
            'user_id'    => auth()->id(),
            'work_date'  => $today,
            'work_start' => now(),
        ]);

        return redirect('/attendance');
    }

    public function endWork()
    {
        $work = Work::where('user_id', auth()->id())
            ->where('work_date', today())
            ->first();

        if ($work && !$work->work_end) {
            $work->update([
                'work_end' => now(),
            ]);
        }
        $work->refresh();
        $this->attendanceService->recalculateTotals($work);
        $work->save();

        return redirect('/attendance');
    }

    public function breakStart()
    {
        $work = Work::where('user_id', auth()->id())
            ->where('work_date', today())
            ->first();

        if ($work) {
            if (!$work->breakTimes()->whereNull('break_end')->exists()) {
                $work->breakTimes()->create([
                    'break_start' => now(),
                ]);
            }
        }
        return redirect('/attendance');
    }

    public function breakEnd()
    {
        $work = Work::where('user_id', auth()->id())
            ->where('work_date', today())
            ->first();

        if ($work) {
            $break = $work->breakTimes()->whereNull('break_end')->first();
            if ($break) {
                $break->update([
                    'break_end' => now(),
                ]);
                $work->unsetRelation('breakTimes');
                $work->load('breakTimes');
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
        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();
        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->copy();
        }
        $works = Work::where('user_id', $userId)
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy('work_date');

        return view('attendance.monthly_list', [
            'dates'       => $dates,
            'works'       => $works,
            'month'       => $month,
            'currentMonth'=> $month,
            'previousMonth'   => Carbon::parse($month)->subMonth()->format('Y-m'),
            'nextMonth'   => Carbon::parse($month)->addMonth()->format('Y-m'),
            'user'        => null,
        ]);
    }

    public function detail(Request $request, $id)
    {
        $user = auth()->user();

        if ($id == self::NEW_WORK_ID) {
            $mode = 'edit';
            $date = $request->query('date');
            $work = (object)[
                'id' => self::NEW_WORK_ID,
                'work_date' => $date,
                'work_start' => null,
                'work_end' => null,
                'remarks' => '',
                'breakTimes' => collect(),
            ];

            $viewData = [
                'work_start' => '',
                'work_end' => '',
                'remarks' => '',
                'breaks' => [1 => ['start' => '', 'end' => '']],
            ];

            return view('attendance.detail', compact('work', 'mode', 'viewData', 'user'));
        }

        $work = Work::with('breakTimes')->findOrFail($id);
        $pastRequestId = $request->query('request_id');
        $pendingRequest = \App\Models\Request::where('work_id', $id)->where('status', self::STATUS_PENDING)->first();

        if ($pastRequestId) {
            $mode = 'approved_log';
            $currentRequest = \App\Models\Request::with('requestDetails')->findOrFail($pastRequestId);
            $viewData = $this->attendanceService->buildViewDataFromDetails($work, $currentRequest);

        } elseif ($pendingRequest) {
            $mode = 'pending';
            $viewData = $this->attendanceService->buildViewDataFromDetails($work, $pendingRequest);

        } else {
            $mode = 'edit';
            $breaks = [];
            foreach ($work->breakTimes as $index => $break) {
                $breaks[$index + 1] = [
                    'start' => optional($break->break_start)->format('H:i'),
                    'end'   => optional($break->break_end)->format('H:i'),
                ];
            }

            if (empty($breaks)) {
                $breaks = [
                    1 => ['start' => '', 'end' => '']
                ];
            }

            $viewData = [
                'work_start' => optional($work->work_start)->format('H:i'),
                'work_end'   => optional($work->work_end)->format('H:i'),
                'remarks'    => $work->remarks,
                'breaks'     => $breaks,
            ];
        }

        return view('attendance.detail', compact('work', 'mode', 'viewData', 'user'));
    }
}