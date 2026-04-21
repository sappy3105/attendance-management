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
            'check_in'      => ['required', 'date_format:H:i'], // 出勤時間は必須
            'check_out'     => ['required', 'date_format:H:i'], // 退勤時間は必須
            'break_start'   => ['nullable', 'array'],           // 外枠が配列であること
            'break_start.*' => ['nullable', 'date_format:H:i'], // 中身の1つ1つが正しい形式であること
            'break_end'     => ['nullable', 'array'],
            'break_end.*'   => ['nullable', 'date_format:H:i'],
            'remarks'       => ['required'], // 備考は必須
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
            'check_in.required'         => '出勤時間を入力してください',
            'check_out.required'        => '退勤時間を入力してください',
            'break_start.array'         => '休憩時間の形式が不正です',
            'break_end.array'           => '休憩時間の形式が不正です',
            'break_start.*.date_format' => '時刻形式で入力してください',
            'break_end.*.date_format'   => '時刻形式で入力してください',
            'remarks.required'          => '備考を記入してください',
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

            // 1. 出勤・退勤の不整合チェック
            // 出勤と退勤、両方の入力がある場合のみチェック
            if ($cCheckIn && $cCheckOut) {
                // 出勤時間が退勤時間より後の時間だったらエラー(gt: greater than)
                if ($cCheckIn->gt($cCheckOut)) {
                    $validator->errors()->add('check_out', '出勤時間もしくは退勤時間が不適切な値です'); //機能要件にあるルール1
                }
            }

            // 休憩時間のループ処理
            foreach ($breakStarts as $index => $start) {
                // 同じ番号（index）の終了時間を取得
                $end = $breakEnds[$index] ?? null;

                // 比較用にCarbonオブジェクト化
                $cStart = $start ? Carbon::parse($start) : null;
                $cEnd   = $end ? Carbon::parse($end) : null;

                // 2. 休憩時間の入力必須チェック
                if (empty($start) && !empty($end)) {
                    $validator->errors()->add("break_start.{$index}", '休憩開始時間を入力してください');
                    continue; // 入力必須エラーが出たら、その行の他のチェックは飛ばす
                }
                if (!empty($start) && empty($end)) {
                    $validator->errors()->add("break_end.{$index}", '休憩終了時間を入力してください');
                    continue; // 必須エラーが出たら、その行の他のチェックは飛ばす
                }

                if ($cStart && $cEnd) {
                    // 3. 休憩の開始・終了の前後チェック
                    // 休憩開始が終了より後(gt)だったらエラー
                    if ($cStart->gt($cEnd)) {
                        $validator->errors()->add("break_end.{$index}", '休憩時間が不適切な値です');
                        continue;
                    }

                    if ($cCheckIn && $cCheckOut) {
                        // 4. 休憩開始が「出勤前」または「退勤後」:機能要件にあるルール2
                        if ($cStart->lt($cCheckIn) || $cStart->gt($cCheckOut)) {
                            $validator->errors()->add("break_start.{$index}", '休憩時間が不適切な値です');
                        }

                        // 5. 休憩終了が「退勤後」:機能要件にあるルール3
                        if ($cEnd->gt($cCheckOut)) {
                            if (!$validator->errors()->has("break_start.{$index}")) {
                                $validator->errors()->add("break_end.{$index}", '休憩時間もしくは退勤時間が不適切な値です');
                            }
                        }
                    }
                }
            }

            //休憩時間同士の比較、休憩時間の重複に関するバリデーション
            // 有効な休憩時間をペアとして抽出し、配列にする
            $validRests = [];
            foreach ($breakStarts as $index => $start) {
                $end = $breakEnds[$index] ?? null;
                if (!empty($start) && !empty($end)) {
                    $validRests[] = [
                        'index' => $index,
                        'start' => Carbon::createFromFormat('H:i', $start),
                        'end'   => Carbon::createFromFormat('H:i', $end),
                    ];
                }
            }

            // 2重ループで全ペアを比較する
            foreach ($validRests as $i => $restA) {
                foreach ($validRests as $j => $restB) {
                    // 同じ行同士の比較はスキップ
                    if ($i === $j) continue;

                    // 重複条件：(A開始 < B終了) かつ (A終了 > B開始)
                    if ($restA['start']->lt($restB['end']) && $restA['end']->gt($restB['start'])) {
                        $errorIndex = $restA['index'];
                        // 既に対象行にエラーが出ていなければ追加
                        if (!$validator->errors()->has("break_start.{$errorIndex}") && !$validator->errors()->has("break_end.{$errorIndex}")) {
                            $validator->errors()->add("break_start.{$errorIndex}", '休憩時間が他の休憩時間と重複しています');
                        }
                    }
                }
            }
        });
    }
}
