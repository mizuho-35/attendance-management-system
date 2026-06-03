<?php

namespace App\Services;

use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use App\Models\Work;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceService
{
    public function getAdjacentDate(string $date, int $days): string
    {
        return Carbon::parse($date)->addDays($days)->toDateString();
    }

    public function getMonthlyListData(int $userId, string $currentMonth): array
    {
        $start = Carbon::parse($currentMonth)->startOfMonth();
        $end = Carbon::parse($currentMonth)->endOfMonth();

        $dates = collect(CarbonPeriod::create($start, $end))->toArray();

        $worksData = Work::where('user_id', $userId)
            ->whereBetween('work_date', [$start, $end])
            ->get()
            ->keyBy(fn ($item) => Carbon::parse($item->work_date)->format('Y-m-d'))
            ->all();

        return [
            'dates' => $dates,
            'works' => $worksData,
            'previousMonth' => $start->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $start->copy()->addMonth()->format('Y-m'),
        ];
    }

    public function getAdminDetailData($id, $date, $pastRequestId): array
    {
        if ($id == 0) {
            return [
                'mode' => 'edit',
                'work' => (object) ['id' => 0, 'work_date' => $date, 'remarks' => '', 'breakTimes' => collect()],
                'viewData' => [
                    'work_start' => '',
                    'work_end' => '',
                    'remarks' => '',
                    'breaks' => [1 => ['start' => '', 'end' => '']],
                ],
                'latestRequest' => null,
            ];
        }

        $work = Work::with('breakTimes')->findOrFail($id);
        $pendingRequest = AttendanceRequest::where('work_id', $id)->where('status', 0)->first();

        if ($pastRequestId) {
            $currentRequest = AttendanceRequest::with('requestDetails')->findOrFail($pastRequestId);
            return [
                'mode' => 'approved_log',
                'work' => $work,
                'viewData' => $this->buildViewDataFromDetails($work, $currentRequest),
                'latestRequest' => null,
            ];
        }

        if ($pendingRequest) {
            return [
                'mode' => 'pending',
                'work' => $work,
                'viewData' => $this->buildViewDataFromDetails($work, $pendingRequest),
                'latestRequest' => $pendingRequest,
            ];
        }

        $breaks = $work->breakTimes->mapWithKeys(fn ($break, $i) => [
            $i + 1 => [
                'start' => $break->break_start ? Carbon::parse($break->break_start)->format('H:i') : '',
                'end' => $break->break_end ? Carbon::parse($break->break_end)->format('H:i') : '',
            ],
        ])->all();

        return [
            'mode' => 'edit',
            'work' => $work,
            'viewData' => [
                'work_start' => $work->work_start ? Carbon::parse($work->work_start)->format('H:i') : '',
                'work_end' => $work->work_end ? Carbon::parse($work->work_end)->format('H:i') : '',
                'remarks' => $work->remarks,
                'breaks' => empty($breaks) ? [1 => ['start' => '', 'end' => '']] : $breaks,
            ],
            'latestRequest' => null,
        ];
    }

    public function updateBreakTimes(Work $work, array $breaksData): void
    {
        $work->breakTimes()->delete();
        $date = $work->work_date;

        foreach ($breaksData as $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;

            if (!empty($start) || !empty($end)) {
                BreakTime::create([
                    'work_id' => $work->id,
                    'break_start' => $start ? "{$date} {$start}:00" : null,
                    'break_end' => $end ? "{$date} {$end}:00" : null,
                ]);
            }
        }

        $work->unsetRelation('breakTimes')->load('breakTimes');
    }

    public function buildViewDataFromDetails($work, $attendanceRequest)
    {
        $requestDetails = $attendanceRequest->requestDetails;

        // 💡 略語（$dt）を排除し $dateTimeString に変更
        $formatTime = fn ($type) => ($dateTimeString = $requestDetails->where('type', $type)->first()?->value_datetime) 
            ? Carbon::parse($dateTimeString)->format('H:i') : '';

        // 💡 略語（$bt, $i）を排除し $breakTime, $index に変更
        $breaks = $work->breakTimes->mapWithKeys(fn ($breakTime, $index) => [
            $index + 1 => [
                'start' => $breakTime->break_start ? Carbon::parse($breakTime->break_start)->format('H:i') : '',
                'end' => $breakTime->break_end ? Carbon::parse($breakTime->break_end)->format('H:i') : '',
            ],
        ])->all();

        $newIndex = count($breaks) + 1;

        // 💡 略語を排除し、変数名を完全にフルスペル化
        foreach ($requestDetails->whereIn('type', [3, 4])->groupBy('break_time_id') as $breakTimeId => $group) {
            $currentIndex = ($breakTimeId === null) 
                ? $newIndex++ 
                : ($work->breakTimes->search(fn ($break) => $break->id == $breakTimeId) + 1);

            // 💡 $sRow, $eRow を $startRow, $endRow に修正
            $startRow = $group->where('type', 3)->first();
            $endRow = $group->where('type', 4)->first();

            $breaks[$currentIndex] = [
                'start' => $startRow ? Carbon::parse($startRow->value_datetime)->format('H:i') : ($breaks[$currentIndex]['start'] ?? ''),
                'end' => $endRow ? Carbon::parse($endRow->value_datetime)->format('H:i') : ($breaks[$currentIndex]['end'] ?? ''),
            ];
        }

        ksort($breaks);

        return [
            'work_start' => $formatTime(1),
            'work_end' => $formatTime(2),
            'remarks' => $attendanceRequest->remarks,
            'breaks' => $breaks,
        ];
    }

    public function recalculateTotals($work)
    {
        $totalBreakMinutes = $work->breakTimes->sum(function ($breakTimeInstance) {
            if ($breakTimeInstance->break_start && $breakTimeInstance->break_end) {
                $start = Carbon::parse($breakTimeInstance->break_start);
                $end = Carbon::parse($breakTimeInstance->break_end);
                return $end->greaterThan($start) ? $end->diffInMinutes($start) : 0;
            }
            return 0;
        });

        $work->break_total = null;
        $work->work_total = null;

        if ($work->work_start && $work->work_end) {
            $startWork = Carbon::parse($work->work_start);
            $endWork = Carbon::parse($work->work_end);

            if ($endWork->greaterThan($startWork)) {
                $totalWorkMinutes = max(0, $endWork->diffInMinutes($startWork) - $totalBreakMinutes);

                $breakHours = floor($totalBreakMinutes / 60);
                $breakMinutes = $totalBreakMinutes % 60;
                $work->break_total = sprintf('%02d:%02d:00', $breakHours, $breakMinutes);

                $workHours = floor($totalWorkMinutes / 60);
                $workMinutes = $totalWorkMinutes % 60;
                $work->work_total = sprintf('%02d:%02d:00', $workHours, $workMinutes);
            }
        }
    }
}