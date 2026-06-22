<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    // クラス内で使用するための変数を宣言
    protected $attendanceService;

    // Laravel の DI（依存性注入）パターンの典型的な書き方
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }


    public function index()
    {
        // 今日の日付を取得してtoDateStringメソッドでY-m-d形式（時刻を切り捨て）に変換して$todayに代入
        $today = now()->toDateString();
        // WorkモデルとbreakTimeモデルを読み込み
        $work = Work::with('breakTimes')
            // ログイン中のユーザー情報のみ取得(Auth::はlaravelの認証機能を扱うためのファザード)
            ->where('user_id', Auth::id())
            // 今日の日付の勤怠データのみ取得
            ->where('work_date', $today)
            // 条件に一致するレコードを一件のみ取得
            ->first();

        // 勤怠ステータスを出勤前に設定
        $status = 'before_work';

        // 今日の勤怠データが存在する場合
        if ($work) {
            // 勤怠データに退勤時間がある場合
            if ($work->work_end) {
                // 勤怠ステータスを退勤ずみに変更
                $status = 'after_work';
            // 勤怠データに休憩終了時間がない場合
            } elseif ($work->breakTimes()->whereNull('break_end')->exists()) {
                // 勤怠ステータスを休憩中にする
                $status = 'on_break';
            // 勤怠データに休憩開始時間がある場合
            } elseif ($work->work_start) {
                // 勤怠ステータスを勤務中にする
                $status = 'working';
            }
        }

        // $statusと$workの変数をviewに返す
        return view('attendance.index', compact('status', 'work'));
    }

    // 出勤ボタンを押された時に実行されるメソッド
    public function startWork()
    {
        // 今日の日付をCarbonで取得
        $today = today();
        // ログイン中のユーザーIDを$userIdに代入
        $userId = Auth::id();
        // ログイン中のユーザーの今日の勤怠データが存在するかチェック
        $exists = Work::where('user_id', $userId)->where('work_date', $today)->exists();
        // 今日の勤怠データが存在しない場合のみ実行
        if (!$exists) {
            // 新しい勤怠データを作成
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
        // ログイン中のユーザーの今日の勤怠データを$workに代入
        $work = Work::where('user_id', Auth::id())->where('work_date', today())->first();
        // 今日の勤怠データがある、かつ、休憩終了時間カラムが存在していない休憩時間テーブルがない場合のみ実行
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