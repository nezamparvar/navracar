<?php

namespace App\Http\Requests;

use App\Models\PipelineStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterQuoteRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'car_label' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'string', Rule::in(['باز', 'در حال پیگیری', 'فروخته شد', 'بسته - موفق', 'بسته - ناموفق'])],
            'stage' => ['nullable', 'integer', Rule::exists('pipeline_stages', 'id')],
            'assigned' => ['nullable', 'string'],
            'show_all' => ['nullable', 'boolean'],
            'show_archived' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'تاریخ شروع باید به فرمت YYYY-MM-DD باشد.',
            'to.date_format' => 'تاریخ پایان باید به فرمت YYYY-MM-DD باشد.',
            'stage.exists' => 'مرحله انتخاب‌شده معتبر نیست.',
            'email.email' => 'ایمیل معتبر نیست.',
        ];
    }
}
