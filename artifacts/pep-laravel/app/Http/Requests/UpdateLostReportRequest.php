<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLostReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_name'              => ['nullable', 'string', 'max:255'],
            'owner_contact'           => ['nullable', 'string', 'max:255'],
            'lost_datetime_from'      => ['nullable', 'date'],
            'lost_datetime_to'        => ['nullable', 'date', 'after_or_equal:lost_datetime_from'],
            'lost_location_estimated' => ['nullable', 'string', 'max:255'],
            'category'                => ['nullable', 'string', 'in:財布・カバン類,衣類,電子機器,傘,その他'],
            'features'                => ['nullable', 'string', 'max:2000'],
            'status'                  => ['nullable', 'string', 'in:探索中,解決済,キャンセル'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.max'              => '氏名は255文字以内で入力してください。',
            'owner_contact.max'           => '連絡先は255文字以内で入力してください。',
            'lost_datetime_from.date'     => '紛失日時（開始）は有効な日時形式で入力してください。',
            'lost_datetime_to.date'       => '紛失日時（終了）は有効な日時形式で入力してください。',
            'lost_datetime_to.after_or_equal' => '紛失日時（終了）は開始日時以降を指定してください。',
            'lost_location_estimated.max' => '紛失場所（推定）は255文字以内で入力してください。',
            'category.in'                 => '選択されたカテゴリは無効です。',
            'features.max'                => '特徴・説明は2000文字以内で入力してください。',
            'status.in'                   => '選択されたステータスは無効です。',
        ];
    }
}
