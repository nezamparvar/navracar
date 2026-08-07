@component('mail::message')
# درخواست استعلام قیمت واردات خودرو — ناوراکار

شماره درخواست: **#{{ $lead->id }}**

**نام مشتری:** {{ $lead->name }}
**شماره تماس:** {{ $lead->phone }}
**ایمیل مشتری:** {{ $lead->email ?: '-' }}
**خودروی انتخابی:** {{ $lead->car_label }}
**دسته خودرو:** {{ $lead->category }}
@if ($lead->notes)
**توضیحات:** {{ $lead->notes }}
@endif

@component('mail::table')
| شرح هزینه | نرخ | مبلغ |
| --- | --- | --- |
@foreach ($breakdown as $row)
| {{ $row['label'] ?? '' }} | {{ $row['rate'] ?? '' }} | {{ $row['amount'] ?? '' }} |
@endforeach
@endcomponent

## جمع‌بندی
@foreach ($totals as $label => $val)
**{{ $label }}:** {{ $val }}
@endforeach

@component('mail::button', ['url' => route('admin.requests.show', $lead)])
مشاهده در پنل مدیریت
@endcomponent

این ایمیل به‌صورت خودکار از سامانه محاسبه‌گر هزینه واردات خودرو ناوراکار ارسال شده است.
@endcomponent
