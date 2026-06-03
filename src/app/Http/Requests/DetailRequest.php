<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DetailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'work_start' => 'nullable',
            'work_end' => 'nullable',
            'breaks.*.start' => 'nullable',
            'breaks.*.end' => 'nullable',
            'remarks' => 'required'
        ];
    }

    public function messages() {
        return [
            'remarks.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $workStart = $this->input('work_start');
            $workEnd = $this->input('work_end');

            if ($workStart && $workEnd && $workStart > $workEnd) {
                $validator->errors()->add('work_start', '出勤時間が不適切な値です');
            }

            foreach ($this->input('breaks', []) as $index => $break) {
                $start = $break['start'] ?? null;
                $end = $break['end'] ?? null;

                if ($start) {
                    if ($workStart && $start < $workStart) {
                        $validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
                    }
                    if ($workEnd && $start > $workEnd) {
                        $validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
                    }
                }

                if ($end) {
                    if ($workEnd && $end > $workEnd) {
                        $validator->errors()->add("breaks.$index.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }

    public function validatedWithUser()
    {
        $id = $this->route('id');

        if ($id && $id != 0) {
            $work = \App\Models\Work::find($id);
            $userId = $work ? $work->user_id : ($this->input('user_id') ?? $this->query('user_id'));
            $date = $work ? $work->work_date : ($this->input('work_date') ?? $this->query('date'));
        } else {
            $userId = $this->input('user_id') ?? $this->query('user_id');
            $date = $this->input('work_date') ?? $this->query('date');
        }

        return [
            'user_id' => $userId,
            'work_date' => $date,
            'work_start' => $this->work_start ? "{$date} {$this->work_start}:00" : null,
            'work_end'  => $this->work_end ? "{$date} {$this->work_end}:00" : null,
            'remarks' => $this->input('remarks'),
        ];
    }
}
