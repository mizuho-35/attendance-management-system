<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetailRequest;
use App\Models\AttendanceRequest;
use App\Models\Work;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    private const NEW_WORK_ID = 0;
    private const STATUS_PENDING = 0;
    private const ROLE_STAFF = 0;

    private const TYPE_WORK_START = 1;
    private const TYPE_WORK_END = 2;
    private const TYPE_BREAK_START = 3;
    private const TYPE_BREAK_END = 4;

    public function store(DetailRequest $request, $workId)
    {
        if ($workId == self::NEW_WORK_ID) {
            $workDate = $request->input('work_date') ?? $request->query('date') ?? request('date');
            $userId = Auth::id();

            $work = Work::updateOrCreate(
                [
                    'user_id' => $userId,
                    'work_date' => $workDate,
                ],
                [
                    'work_start' => $request->work_start ? "{$workDate} {$request->work_start}:00" : null,
                    'work_end' => $request->work_end ? "{$workDate} {$request->work_end}:00" : null,
                ]
            );

            $workId = $work->id;
            $breakTimes = collect();
        } else {
            $work = Work::with('breakTimes')->findOrFail($workId);
            $workDate = $work->work_date;
            $breakTimes = $work->breakTimes->values();
        }

        $requestModel = AttendanceRequest::create([
            'user_id' => Auth::id(),
            'work_id' => $workId,
            'work_date' => $workDate,
            'remarks' => $request->remarks,
            'status' => self::STATUS_PENDING,
        ]);

        if ($request->work_start) {
            $requestModel->requestDetails()->create([
                'type' => self::TYPE_WORK_START,
                'value_datetime' => "{$workDate} {$request->work_start}:00",
            ]);
        }

        if ($request->work_end) {
            $requestModel->requestDetails()->create([
                'type' => self::TYPE_WORK_END,
                'value_datetime' => "{$workDate} {$request->work_end}:00",
            ]);
        }

        $breaks = $request->input('breaks', []);
        $existingBreaks = $breakTimes->all();

        foreach ($breaks as $index => $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;

            if (empty($start) && empty($end)) {
                continue;
            }

            $currentBreakTime = $existingBreaks[$index - 1] ?? null;
            $breakTimeId = $currentBreakTime ? $currentBreakTime->id : null;

            if (!empty($start)) {
                $requestModel->requestDetails()->create([
                    'type' => self::TYPE_BREAK_START,
                    'break_time_id' => $breakTimeId,
                    'value_datetime' => "{$workDate} {$start}:00",
                ]);
            }
            if (!empty($end)) {
                $requestModel->requestDetails()->create([
                    'type' => self::TYPE_BREAK_END,
                    'break_time_id' => $breakTimeId,
                    'value_datetime' => "{$workDate} {$end}:00",
                ]);
            }
        }

        return redirect()->route('attendance.detail', ['id' => $workId])
            ->with('success', '修正申請を送信しました');
    }

    public function index()
    {
        $user = Auth::user();
        $status = request('status');

        $query = AttendanceRequest::with(['requestDetails', 'work', 'user'])
            ->orderBy('created_at', 'desc');

        if ($user->role === self::ROLE_STAFF) {
            $query->where('user_id', $user->id);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        } else {
            $query->where('status', self::STATUS_PENDING);
        }

        $requests = $query->get();

        return view('request.list', compact('requests'));
    }
}