<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'check_in'    => ['required'],
            'check_out'   => ['required'],
            'break_start' => ['array'],
            'break_end'   => ['array'],
            'remarks'     => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.required'  => '出勤時間を入力してください',
            'check_out.required' => '退勤時間を入力してください',
            'remarks.required'   => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $checkIn  = $this->input('check_in');
            $checkOut = $this->input('check_out');
            $remarks  = $this->input('remarks');

            // 1. 出勤・退勤の不整合チェック
            if ($checkIn && $checkOut) {
                if (Carbon::parse($checkIn)->gt(Carbon::parse($checkOut))) {
                    $validator->errors()->add('check_out', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            // 休憩時間のループ処理
            $breakStarts = $this->input('break_start', []);
            $breakEnds   = $this->input('break_end', []);

            foreach ($breakStarts as $index => $start) {
                $end = $breakEnds[$index] ?? null;

                // 2. 休憩開始時間の入力チェック（終了が入っている場合）
                if (empty($start) && !empty($end)) {
                    $validator->errors()->add("break_start.{$index}", '休憩開始時間を入力してください');
                }

                // 3. 休憩終了時間の入力チェック（開始が入っている場合）
                if (!empty($start) && empty($end)) {
                    $validator->errors()->add("break_end.{$index}", '休憩終了時間を入力してください');
                }

                if ($start && $end) {
                    $cStart    = Carbon::parse($start);
                    $cEnd      = Carbon::parse($end);
                    $cCheckIn  = Carbon::parse($checkIn);
                    $cCheckOut = Carbon::parse($checkOut);

                    // 4. 休憩終了が開始より後であること
                    if ($cStart->gt($cEnd)) {
                        $validator->errors()->add("break_end.{$index}", '休憩終了時間は休憩開始時間より後の時間を入力してください');
                    }

                    // 5. 休憩開始が出勤前、または退勤後（勤務時間外）
                    if ($cStart->lt($cCheckIn) || $cStart->gt($cCheckOut)) {
                        $validator->errors()->add("break_start.{$index}", '休憩時間が不適切な値です');
                    }

                    // 6. 休憩終了が退勤より後
                    if ($cEnd->gt($cCheckOut)) {
                        $validator->errors()->add("break_end.{$index}", '休憩時間もしくは退勤時間が不適切な値です');
                    }

                    // 7. 休憩終了が出勤前（勤務時間外）
                    if ($cEnd->lt($cCheckIn)) {
                        $validator->errors()->add("break_end.{$index}", '休憩時間が不適当な値です');
                    }
                }
            }
        });
    }
}
