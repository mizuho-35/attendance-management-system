<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\BreakTime;

class AttendanceService
{
    public function buildViewDataFromDetails($work, $req)
    {
        $details = $req->requestDetails;

        $workStart = $details->where('type', 1)->first()?->value_datetime;
        $workEnd   = $details->where('type', 2)->first()?->value_datetime;

        $viewWorkStart = $workStart ? Carbon::parse($workStart)->format('H:i') : '';
        $viewWorkEnd   = $workEnd ? Carbon::parse($workEnd)->format('H:i') : '';

        $breaks = [];
        foreach ($work->breakTimes as $i => $bt) {
            $breaks[$i + 1] = [
                'start' => $bt->break_start ? Carbon::parse($bt->break_start)->format('H:i') : '',
                'end'   => $bt->break_end   ? Carbon::parse($bt->break_end)->format('H:i') : '',
            ];
        }

        $rows = $details->whereIn('type', [3, 4])->values();
        $newIndex = count($breaks) + 1;

        foreach ($rows->groupBy('break_time_id') as $breakTimeId => $group) {
            if ($breakTimeId === null) {
                $index = $newIndex;
                $newIndex++;
            } else {
                $index = $work->breakTimes->search(fn($b) => $b->id == $breakTimeId) + 1;
            }

            $sRow = $group->where('type', 3)->first();
            $eRow = $group->where('type', 4)->first();

            $breaks[$index] = [
                'start' => $sRow ? Carbon::parse($sRow->value_datetime)->format('H:i') : ($breaks[$index]['start'] ?? ''),
                'end'   => $eRow ? Carbon::parse($eRow->value_datetime)->format('H:i') : ($breaks[$index]['end'] ?? ''),
            ];
        }

        ksort($breaks);

        return [
            'work_start' => $viewWorkStart,
            'work_end'   => $viewWorkEnd,
            'remarks'    => $req->remarks,
            'breaks'     => $breaks,
        ];
    }

    public function recalculateTotals($work)
    {
        $totalBreakMinutes = 0;

        foreach ($work->breakTimes as $break) {
            if ($break->break_start && $break->break_end) {
                $cbStart = Carbon::parse($break->break_start);
                $cbEnd   = Carbon::parse($break->break_end);

                if ($cbEnd->greaterThan($cbStart)) {
                    $totalBreakMinutes += $cbEnd->diffInMinutes($cbStart);
                }
            }
        }

        $work->break_total = null;
        $work->work_total  = null;

        if ($work->work_start && $work->work_end) {
            $startWork = Carbon::parse($work->work_start);
            $endWork   = Carbon::parse($work->work_end);

            if ($endWork->greaterThan($startWork)) {
                $totalGrossMinutes = $endWork->diffInMinutes($startWork);
                $totalWorkMinutes = $totalGrossMinutes - $totalBreakMinutes;
                if ($totalWorkMinutes < 0) $totalWorkMinutes = 0;

                $breakH = floor($totalBreakMinutes / 60);
                $breakM = $totalBreakMinutes % 60;
                $work->break_total = sprintf('%02d:%02d:00', $breakH, $breakM);

                $workH = floor($totalWorkMinutes / 60);
                $workM = $totalWorkMinutes % 60;
                $work->work_total = sprintf('%02d:%02d:00', $workH, $workM);
            }
        }
    }
}