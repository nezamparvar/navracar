@component('mail::message')
# فرصت فروش جدید — ناوراکار

**کارشناس:** {{ $staffName }}
**نام مشتری:** {{ $lead->name }}
**تلفن:** {{ $lead->phone }}
**خودروی مورد نظر:** {{ $lead->car_label }}
**بودجه:** {{ $lead->budget_range }}
**شهر:** {{ $lead->city }}
**وضعیت:** {{ $lead->follow_up_status }}
@if ($lead->next_call_date)
**تماس بعدی:** {{ $lead->next_call_date->format('Y-m-d') }}
@endif
@if ($lead->notes)
**توضیحات:** {{ $lead->notes }}
@endif

@component('mail::button', ['url' => route('admin.requests.show', $lead)])
مشاهده در پنل مدیریت
@endcomponent

این ایمیل به‌صورت خودکار از سامانه ناوراکار ارسال شده است.
@endcomponent
