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
    public function rules(): array //単独の項目のチェック
    {
        return [
            'check_in'    => ['required'], // 出勤時間は必須
            'check_out'   => ['required'], // 退勤時間は必須
            'break_start' => ['required', 'array'], // 休憩開始は（複数ある可能性有りのため）配列形式
            'break_end'   => ['array'], // 休憩終了は（複数ある可能性有りのため）配列形式
            'remarks'     => ['required'], // 備考は必須
        ];
    }

    // エラーが出た時の項目名を日本語に変換
    public function attributes(): array
    {
        return [
            'check_in'      => '出勤時間',
            'check_out'     => '退勤時間',
            'break_start.*' => '休憩開始時間',
            'break_end.*'   => '休憩終了時間',
            'remarks'       => '備考',
        ];
    }

    public function messages(): array
    {
        return [
            'check_in.required'  => '出勤時間を入力してください',
            'check_out.required' => '退勤時間を入力してください',
            'break_start.array'  => '休憩時間の形式が不正です',
            'break_end.array'    => '休憩時間の形式が不正です',
            'remarks.required'   => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) { //項目同士の比較 など、複雑なロジックが必要なチェック
            // 入力された「出勤」「退勤」を一旦変数に入れる
            $checkIn  = $this->input('check_in');
            $checkOut = $this->input('check_out');
            // 休憩の開始・終了配列を取得。空なら空配列を入れる
            $breakStarts = $this->input('break_start', []);
            $breakEnds   = $this->input('break_end', []);

            // 比較用にCarbonオブジェクト化
            $cCheckIn  = $checkIn ? Carbon::parse($checkIn) : null;
            $cCheckOut = $checkOut ? Carbon::parse($checkOut) : null;

            // --- 重複エラーを避けるための共通関数 ---
            // $index: 休憩の行番号
            // $key: 'break_start' または 'break_end'
            // $message: エラー文言
            // $addUniqueError = function ($index, $key, $message) use ($validator) {
            //     $allErrors = $validator->errors();
            //     $currentBreakErrors = array_merge(
            //         $allErrors->get("break_start.{$index}"),
            //         $allErrors->get("break_end.{$index}")
            //     );

            //     // すでに同じメッセージが存在しなければ追加する
            //     if (!in_array($message, $currentBreakErrors)) {
            //         $allErrors->add("{$key}.{$index}", $message);
            //     }
            // };

            // 1. 出勤・退勤の不整合チェック
            // 出勤と退勤、両方の入力がある場合のみチェック
            if ($cCheckIn && $cCheckOut) {
                // 出勤(gt: greater than)退勤より後の時間だったらエラー
                if ($cCheckIn->gt($cCheckOut)) {
                    $validator->errors()->add('check_out', '出勤時間もしくは退勤時間が不適切な値です'); //機能要件にあるルール1
                }
            }

            // 休憩時間のループ処理
            foreach ($breakStarts as $index => $start) {
                // 同じ番号（index）の終了時間を取得
                $end = $breakEnds[$index] ?? null;

                // 比較用にCarbonオブジェクト化（休憩時間はループごとに変わるのでループ内で定義）
                $cStart = $start ? Carbon::parse($start) : null;
                $cEnd   = $end ? Carbon::parse($end) : null;

                // 2. 休憩開始が単独で「出勤前」または「退勤後」
                if ($cStart && $cCheckIn && $cCheckOut) {
                    if ($cStart->lt($cCheckIn) || $cStart->gt($cCheckOut)) {
                        $validator->errors()->add("break_start.{$index}", '休憩時間が不適切な値です'); //機能要件にあるルール2
                    }
                }

                // 3. 休憩終了が単独で「退勤後」または「出勤前」
                if ($cEnd && $cCheckIn && $cCheckOut) {
                    if ($cEnd->lt($cCheckIn) || $cEnd->gt($cCheckOut)) {
                        $validator->errors()->add("break_end.{$index}", '休憩時間もしくは退勤時間が不適切な値です'); //機能要件にあるルール3
                    }
                }

                // 4. 休憩時間の入力必須チェック
                if (empty($start) && !empty($end)) {
                    $validator->errors()->add("break_start.{$index}", '休憩開始時間を入力してください');
                }
                if (!empty($start) && empty($end)) {
                    $validator->errors()->add("break_end.{$index}", '休憩終了時間を入力してください');
                }

                // 5. 休憩の開始・終了の前後チェック
                if ($start && $end) {
                    // 休憩開始が終了より後(gt)だったらエラー
                    if ($cStart->gt($cEnd)) {
                        $validator->errors()->add("break_end.{$index}", '休憩終了時間は休憩開始時間より後の時間を入力してください');
                    }
                }
            }
        });
    }
}
