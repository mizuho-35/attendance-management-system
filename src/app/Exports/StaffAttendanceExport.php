<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffAttendanceExport
{
    public function download(int $userId, string $month): StreamedResponse
    {
        $user = User::find($userId);
        if (!$user) {
            dd("エラー：usersテーブルに ID: {$userId} のスタッフが存在しません。データを確認してください。");
        }

        $startOfMonth = Carbon::parse($month)->startOfMonth();
        $endOfMonth   = Carbon::parse($month)->endOfMonth();

        $attendanceData = Work::where('user_id', $userId)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date', 'asc')
            ->get();

        if ($attendanceData->isEmpty()) {
            dd("エラー：ユーザー「{$user->name}」の {$month}分 の勤怠データ（worksテーブル）が1件も見つかりませんでした。");
        }

        $response = new StreamedResponse(function () use ($attendanceData) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, pack('C*', 0xEF, 0xBB, 0xBF));
            fputcsv($stream, ['日付', '出勤時間', '退勤時間', '休憩時間', '合計勤務時間']);

            foreach ($attendanceData as $data) {
                fputcsv($stream, [
                    $data->work_date,
                    $data->work_start ? Carbon::parse($data->work_start)->format('H:i') : '',
                    $data->work_end ? Carbon::parse($data->work_end)->format('H:i') : '',
                    $data->break_total ? substr($data->break_total, 0, 5) : '',
                    $data->work_total ? substr($data->work_total, 0, 5) : '',
                ]);
            }
            fclose($stream);
        });

        $fileName = sprintf('%s_%s_勤怠.csv', $user->name, $month);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$fileName}\"");

        return $response;
    }
}