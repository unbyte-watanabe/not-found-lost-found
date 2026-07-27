<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFoundItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'         => ['nullable', 'string', 'in:財布・カバン類,衣類,電子機器,傘,その他'],
            'sub_category'     => ['nullable', 'string', 'max:255'],
            'features'         => ['nullable', 'string', 'max:2000'],
            'found_datetime'   => ['nullable', 'date'],
            'found_location'   => ['nullable', 'string', 'max:255'],
            'image_url'        => ['nullable', 'url', 'max:500'],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'finder_name'      => ['nullable', 'string', 'max:255'],
            'finder_contact'   => ['nullable', 'string', 'max:255'],
            'rights_waived'    => ['nullable', 'boolean'],
            'status'           => ['nullable', 'string', 'in:保管中,返還済,警察提出済,期間満了処分'],
            'returned_at'      => ['nullable', 'date'],
            'returned_to'      => ['nullable', 'string', 'max:255'],
            'returned_by'      => ['nullable', 'string', 'max:255'],
            'identity_verified'=> ['nullable', 'boolean'],
            'receipt_signed'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.in'          => '選択されたカテゴリは無効です。',
            'features.max'         => '特徴・説明は2000文字以内で入力してください。',
            'found_datetime.date'  => '発見日時は有効な日時形式で入力してください。',
            'found_location.max'   => '発見場所は255文字以内で入力してください。',
            'image_url.url'        => '画像URLは有効なURL形式で入力してください。',
            'image_url.max'        => '画像URLは500文字以内で入力してください。',
            'storage_location.max' => '保管場所は255文字以内で入力してください。',
            'finder_name.max'      => '発見者名は255文字以内で入力してください。',
            'finder_contact.max'   => '発見者連絡先は255文字以内で入力してください。',
            'sub_category.max'     => 'サブカテゴリは255文字以内で入力してください。',
            'status.in'            => '選択されたステータスは無効です。',
        ];
    }
}
