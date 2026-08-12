<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\LeadFormSubmitted;
use App\Models\AdminUser;
use App\Models\LeadActivity;
use App\Models\QuoteRequest;
use App\Services\GeoLookupService;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LeadFormController extends Controller
{
    public const CITIES = [
        'تهران', 'کرج', 'مشهد', 'اصفهان', 'شیراز', 'تبریز', 'اهواز', 'قم', 'کرمانشاه', 'ارومیه',
        'رشت', 'زاهدان', 'کرمان', 'اراک', 'یزد', 'اردبیل', 'بندرعباس', 'قزوین', 'ساری', 'همدان', 'سایر',
    ];

    public const COUNTRIES = [
        'ایران', 'امارات متحده عربی', 'ترکیه', 'عراق', 'افغانستان', 'آلمان', 'کانادا',
        'آمریکا', 'انگلستان', 'استرالیا', 'سوئد', 'هلند', 'فرانسه', 'سایر',
    ];

    public const CAR_BRANDS = [
        'Mercedes-Benz', 'BMW', 'Acura', 'Volkswagen', 'Audi', 'Toyota', 'Lexus', 'Hyundai', 'Kia', 'Genesis',
        'Honda', 'Peugeot', 'Nissan', 'Infiniti', 'Mazda', 'Mitsubishi', 'Suzuki', 'Land Rover', 'Jaguar', 'Volvo',
        'Cupra', 'Skoda', 'Subaru', 'Mini', 'Dacia', 'BYD', 'Fangchengbao', 'MG', 'Changan', 'Haval', 'NIO', 'Tank',
        'Voyah', 'Dongfeng', 'Xpeng', 'Alfa Romeo', 'Avatar', 'Xiaomi', 'Opel', 'Geely', 'SsangYong', 'LiAuto',
        'Yangwang', 'Fiat', 'Maextro', 'M-Hero', 'ORA', 'Denza', 'Citroen', 'Renault',
    ];

    public function create()
    {
        $staff = AdminUser::orderByRaw('full_name is null')->orderBy('full_name')->orderBy('username')->get();

        return view('public.lead-form', [
            'title' => 'ثبت گزارش تماس فروش | ناوراکار',
            'staff' => $staff,
            'carBrands' => self::CAR_BRANDS,
            'cities' => self::CITIES,
            'countries' => self::COUNTRIES,
        ]);
    }

    public function store(Request $request, GeoLookupService $geo)
    {
        // Honeypot: bots fill hidden fields — silently report success without saving.
        if (! empty($request->input('website'))) {
            return response()->json(['success' => true, 'message' => 'گزارش با موفقیت ثبت شد.']);
        }

        $loadedAt = (float) $request->input('formLoadedAt', 0);
        if ($loadedAt > 0 && (microtime(true) * 1000 - $loadedAt) < 1500) {
            return response()->json(['success' => false, 'message' => 'لطفاً کمی صبر کنید و دوباره تلاش کنید.'], 422);
        }

        $data = $request->validate([
            'userId' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email'],
            'budget' => ['required', 'string', 'max:100'],
            'carInterest' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'nextCall' => ['nullable', 'date'],
        ]);

        $staffUser = AdminUser::find($data['userId']);
        if (! $staffUser) {
            return response()->json(['success' => false, 'message' => 'کارشناس انتخاب‌شده معتبر نیست. لطفاً دوباره از لیست انتخاب کنید.'], 422);
        }

        $geoData = $geo->lookup($request->ip());

        $lead = QuoteRequest::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'car_label' => $data['carInterest'],
            'category' => '',
            'breakdown_json' => '[]',
            'totals_json' => '{}',
            'total_with_profit' => 0,
            'email_sent' => false,
            'source' => $data['source'],
            'budget_range' => $data['budget'],
            'country' => $data['country'] ?: $geoData['country'],
            'city' => $data['city'] ?: $geoData['city'],
            'assigned_to' => $staffUser->id,
            'created_by' => $staffUser->id,
            'follow_up_status' => $data['status'],
            'next_call_date' => $data['nextCall'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        $staffName = $staffUser->displayName();
        LeadActivity::create([
            'request_id' => $lead->id,
            'admin_user_id' => $staffUser->id,
            'activity_type' => 'note',
            'note' => 'فرصت جدید از فرم عمومی توسط '.$staffName.' ثبت شد (منبع: '.$data['source'].')',
        ]);

        ActivityLogger::info('فرم عمومی فروش با موفقیت ثبت شد', ['id' => $lead->id, 'staff' => $staffName, 'name' => $lead->name, 'phone' => $lead->phone]);

        try {
            Mail::to(config('navaracar.notify_email'))->send(new LeadFormSubmitted($lead, $staffName));
        } catch (\Throwable $e) {
            ActivityLogger::error('ارسال ایمیل اطلاع‌رسانی فرصت جدید ناموفق بود', ['error' => $e->getMessage()]);
        }

        return response()->json(['success' => true, 'id' => $lead->id, 'message' => 'گزارش با موفقیت ثبت شد.']);
    }
}
