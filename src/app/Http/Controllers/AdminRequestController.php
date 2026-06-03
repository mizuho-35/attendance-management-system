<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRequest;
use App\Models\Work;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    protected $attendanceService;

    private const STATUS_PENDING = 0;
    private const STATUS_APPROVED = 1;

    private const TYPE_WORK_START = 1;
    private const TYPE_WORK_END = 2;
    private const TYPE_BREAK_START = 3;
    private const TYPE_BREAK_END = 4;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function show(Request $request, $request_id)
    {
        $latestRequest = AttendanceRequest::with('requestDetails')->findOrFail($request_id);

        $work = Work::with(['user', 'breakTimes'])->findOrFail($latestRequest->work_id);
        $user = $work->user;

        $mode = ($latestRequest->status == self::STATUS_PENDING) ? 'pending' : 'approved_log';

        $viewData = $this->attendanceService->buildViewDataFromDetails($work, $latestRequest);

        return view('attendance.admin_detail', compact('work', 'user', 'mode', 'viewData', 'latestRequest'));
    }

    public function approve($requestId)
    {
        $requestModel = AttendanceRequest::with('requestDetails')->findOrFail($requestId);

        if ($requestModel->work_id === null) {
            $work = Work::create([
                'user_id' => $requestModel->user_id,
                'work_date' => $requestModel->work_date,
                'work_start' => null,
                'work_end' => null,
                'remarks' => $requestModel->remarks,
            ]);
            $requestModel->update(['work_id' => $work->id]);
        } else {
            $work = Work::with('breakTimes')->findOrFail($requestModel->work_id);
        }

        $details = $requestModel->requestDetails;

        $start = $details->where('type', self::TYPE_WORK_START)->first();
        if ($start) {
            $work->work_start = $start->value_datetime;
        }

        $end = $details->where('type', self::TYPE_WORK_END)->first();
        if ($end) {
            $work->work_end = $end->value_datetime;
        }

        if (!empty($requestModel->remarks)) {
            $work->remarks = $requestModel->remarks;
        }

        $existingBreaks = $work->breakTimes;
        $breakGroups = $details->whereIn('type', [self::TYPE_BREAK_START, self::TYPE_BREAK_END])->groupBy('break_time_id');

        foreach ($breakGroups as $breakTimeId => $group) {
            $startRow = $group->where('type', self::TYPE_BREAK_START)->first();
            $endRow = $group->where('type', self::TYPE_BREAK_END)->first();

            if ($breakTimeId) {
                $break = $existingBreaks->where('id', $breakTimeId)->first();
                if ($break) {
                    if ($startRow) $break->break_start = $startRow->value_datetime;
                    if ($endRow) $break->break_end = $endRow->value_datetime;
                    $break->save();
                }
            } else {
                $work->breakTimes()->create([
                    'break_start' => $startRow ? $startRow->value_datetime : null,
                    'break_end' => $endRow ? $endRow->value_datetime : null,
                ]);
            }
        }

        $work->unsetRelation('breakTimes')->load('breakTimes');
        $this->attendanceService->recalculateTotals($work);
        $work->save();

        $requestModel->update(['status' => self::STATUS_APPROVED]);

        return redirect()->route('admin.request.show', $requestModel->id)
            ->with('success', '修正申請を承認しました');
    }
}