<x-layouts.admin :page-title="$pageTitle" :page-subtitle="$pageSubtitle">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-5">
        @csrf

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <div class="font-extrabold">لطفاً خطاهای تنظیمات را اصلاح کنید.</div>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $percentageFields = [
                'customsFixed' => ['حقوق گمرکی ثابت', 'درصد از ارزش گمرکی'],
                'gasolineLevy' => ['عوارض بنزین‌سوز', 'درصد از ارزش گمرکی'],
                'fobLevy' => ['عوارض فوب', 'درصد از ارزش گمرکی'],
                'vat' => ['مالیات ارزش افزوده', 'درصد از ارزش گمرکی + عوارض تعرفه'],
                'advanceImportTax' => ['مالیات علی‌الحساب واردات', 'درصد از ارزش گمرکی + عوارض تعرفه'],
                'redCrescent' => ['عوارض هلال احمر', 'درصد از عوارض تعرفه'],
                'customsSupervision' => ['حق نظارت کارشناسان گمرک', 'درصد از عوارض تعرفه'],
                'wasteLevy' => ['عوارض پسماند کالا', 'درصد از ارزش گمرکی'],
                'standardFee' => ['هزینه استاندارد', 'درصد از ارزش گمرکی'],
                'registration' => ['عوارض شماره‌گذاری راهور', 'درصد از ارزش گمرکی'],
                'transferTax' => ['مالیات نقل و انتقال', 'درصد از ارزش گمرکی'],
                'municipal' => ['عوارض سالانه شهرداری', 'درصد از ارزش گمرکی'],
                'individualPerson' => ['عوارض شخص حقیقی', 'درصد از ارزش گمرکی'],
                'serviceFee' => ['کارمزد ناوراکار', 'درصد از پایه فعلی کارمزد؛ قیمت خودرو و انبارداری در پایه نیست'],
            ];
        @endphp

        <x-card title="درصدهای گمرکی، شماره‌گذاری و کارمزد" icon="target"
                subtitle="تمام درصدهای فعال موتور قیمت‌گذاری از این بخش خوانده می‌شوند و بلافاصله روی محاسبات جدید اثر دارند.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($percentageFields as $key => [$label, $description])
                    <div>
                        <label class="mb-1 block text-xs font-bold text-ink-500">{{ $label }} (درصد)</label>
                        <input type="number" step="0.001" min="0" max="500"
                               name="pricing_percentages[{{ $key }}]"
                               value="{{ old('pricing_percentages.'.$key, $pricingSettings['percentages'][$key]) }}" required
                               class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                        <p class="mt-1 text-[11px] text-ink-400">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="جدول تعداد گواهی اسقاط" icon="target"
                subtitle="مرز آستانه در حالت مساوی داخل ستون «تا آستانه» باقی می‌ماند.">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ink-50 text-ink-500 dark:bg-white/5">
                        <tr>
                            <th class="p-2.5 text-right">رتبه انرژی</th>
                            <th class="p-2.5 text-right">تا و مساوی آستانه (تعداد گواهی)</th>
                            <th class="p-2.5 text-right">بالاتر از آستانه (تعداد گواهی)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (['ab' => 'AB', 'cd' => 'CD', 'efg' => 'EFG'] as $tier => $tierLabel)
                            <tr class="border-t border-ink-100 dark:border-white/5">
                                <td class="p-2.5 font-bold">{{ $tierLabel }}</td>
                                @foreach (['upto', 'above'] as $bracket)
                                    <td class="p-2.5">
                                        <input type="number" step="1" min="0" max="100"
                                               name="scrap_counts[{{ $tier }}][{{ $bracket }}]"
                                               value="{{ old('scrap_counts.'.$tier.'.'.$bracket, $pricingSettings['scrapCertificateCounts'][$tier][$bracket]) }}" required
                                               class="w-28 rounded-lg border border-ink-200 bg-ink-50 px-2 py-1.5 num-font dark:border-white/10 dark:bg-white/5">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="نرخ ارز" icon="target" class="max-w-lg">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">نرخ ارز آزاد (تومان به ازای هر درهم)</label>
                    <input type="number" step="1" name="free_rate" value="{{ old('free_rate', $freeRate) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                    <p class="mt-1 text-[11px] text-ink-400">همین عدد به‌عنوان «نرخ درهم امروز» به مشتری در صفحات قیمت خودروها نمایش داده می‌شود.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">نرخ ارز گمرک (تومان به ازای هر درهم)</label>
                    <input type="number" step="1" name="customs_rate" value="{{ old('customs_rate', $customsRate) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">درصد کاهش پیشنهادی ارزش گمرکی</label>
                    <input type="number" step="0.1" min="0" max="100" name="customs_value_discount_percent" value="{{ old('customs_value_discount_percent', $customsValueDiscountPercent) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                    <p class="mt-1 text-[11px] text-ink-400">فقط برای پیشنهاد اولیه استفاده می‌شود و کاربر می‌تواند ارزش گمرکی را دستی تغییر دهد.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">نرخ تبدیل دلار به درهم</label>
                    <input type="number" step="0.0001" name="usd_to_aed_rate" value="{{ old('usd_to_aed_rate', $usdToAedRate) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                    <p class="mt-1 text-[11px] text-ink-400">برای کاربرانی که قیمت خودرو را به دلار می‌دانند نه درهم — در محاسبه‌گر و صفحهٔ آگهی قابل انتخاب است.</p>
                </div>
            </div>
        </x-card>

        <x-card title="تنظیمات وام و اقساط" icon="target"
                subtitle="برای نمایش جدول اقساط در صفحهٔ محاسبهٔ هزینهٔ هر آگهی خودرو.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">حداکثر مبلغ وام (تومان)</label>
                    <input type="number" step="1" name="loan_max_amount_toman" value="{{ old('loan_max_amount_toman', $loanMaxAmountToman) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">مدت‌های بازپرداخت (سال)</label>
                    <input type="text" dir="ltr" name="loan_term_years" value="{{ old('loan_term_years', $loanTermYears) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                    <p class="mt-1 text-[11px] text-ink-400">چند مدت را با کاما جدا بنویسید، مثلاً <span class="num-font">2,3,5</span> — برای هر مدت یک ردیف قسط جداگانه به مشتری نمایش داده می‌شود.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">نرخ بهره سالانه (٪)</label>
                    <input type="number" step="0.1" name="loan_interest_rate_percent" value="{{ old('loan_interest_rate_percent', $loanInterestRatePercent) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                </div>
            </div>
        </x-card>

        <x-card title="هزینه‌های ثابت موتور محاسبات" icon="target"
                subtitle="این مقادیر در همه محاسبات عمومی و پیش‌فاکتورها مستقیماً از تنظیمات سرور خوانده می‌شوند و برای مشتری قابل تغییر نیستند.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">هزینه صدور مجوزها (درهم)</label>
                    <input type="number" step="1" name="license_fee_aed" value="{{ old('license_fee_aed', $licenseFeeAed) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">حمل دریایی (درهم)</label>
                    <input type="number" step="1" name="sea_freight_aed" value="{{ old('sea_freight_aed', $seaFreightAed) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">انبارداری و دموراژ (تومان)</label>
                    <input type="number" step="1" name="storage_toman" value="{{ old('storage_toman', $storageToman) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">مدت زمان تحویل پیش‌فرض (روز کاری)</label>
                    <input type="number" step="1" name="default_delivery_days" value="{{ old('default_delivery_days', $defaultDeliveryDays) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                    <p class="mt-1 text-[11px] text-ink-400">هنگام ثبت آگهی جدید از دابیزل استفاده می‌شود؛ برای هر آگهی هم قابل تغییر دستی است.</p>
                </div>
            </div>
        </x-card>

        <x-card title="گواهی اسقاط خودرو فرسوده" icon="target"
                subtitle="محاسبه بر اساس رتبه انرژی، آستانه قیمت و جدول تعداد گواهی انجام می‌شود؛ نرخ خرید، آستانه و تعدادها همگی از همین صفحه مدیریت می‌شوند.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">نرخ خرید هر گواهی اسقاط (تومان)</label>
                    <input type="number" step="1" name="scrap_cert_price_toman" value="{{ old('scrap_cert_price_toman', $scrapCertPriceToman) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                    <p class="mt-1 text-[11px] text-ink-400">نرخ روز بازار گواهی اسقاط — حتماً بررسی و به‌روز نگه دارید.</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">آستانه قیمت گمرکی خودرو (درهم)</label>
                    <input type="number" step="1" name="scrap_threshold_aed" value="{{ old('scrap_threshold_aed', $scrapThresholdAed) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                    <p class="mt-1 text-[11px] text-ink-400">معادل آستانه ۱۵ هزار یورو آیین‌نامه (سواری) — بر اساس نرخ روز یورو/درهم تنظیم کنید.</p>
                </div>
            </div>
        </x-card>

        <x-card title="دسته‌بندی و تعرفه گمرکی خودرو" icon="car"
                subtitle="عوارض گمرکی بر اساس تعرفه (٪ از ارزش گمرکی) و رتبه انرژی هر دسته (برای محاسبه تعداد گواهی اسقاط) — مستقیم روی جدول هزینه هر خودرو اثر می‌گذارد.">
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm">
                    <thead class="bg-ink-50 text-ink-500 dark:bg-white/5 dark:text-ink-400">
                        <tr>
                            <th class="p-2.5 text-right">دسته خودرو</th>
                            <th class="p-2.5 text-right">عوارض گمرکی بر اساس تعرفه (٪)</th>
                            <th class="p-2.5 text-right">رتبه انرژی (گواهی اسقاط)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $id => $cat)
                            <tr class="border-t border-ink-100 dark:border-white/5">
                                <td class="p-2.5 font-semibold">{{ $cat['label'] }}</td>
                                <td class="p-2.5">
                                    <input type="number" step="0.01" name="tariff_percent[{{ $id }}]"
                                           value="{{ old('tariff_percent.'.$id, $cat['coef'] * 100) }}"
                                           class="w-24 rounded-lg border border-ink-200 bg-ink-50 px-2 py-1.5 text-sm num-font dark:border-white/10 dark:bg-white/5">
                                </td>
                                <td class="p-2.5">
                                    <select name="scrap_tier[{{ $id }}]" class="rounded-lg border border-ink-200 bg-ink-50 px-2 py-1.5 text-sm dark:border-white/10 dark:bg-white/5">
                                        <option value="ab" @selected(old('scrap_tier.'.$id, $cat['tier']) === 'ab')>(ای) و (بی) — بهترین</option>
                                        <option value="cd" @selected(old('scrap_tier.'.$id, $cat['tier']) === 'cd')>(سی) و (دی) — میانی</option>
                                        <option value="efg" @selected(old('scrap_tier.'.$id, $cat['tier']) === 'efg')>(ای)،(اف) و (جی) — ضعیف‌ترین</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="شماره‌های تماس سایت" icon="target" class="max-w-lg"
                subtitle="در صفحه هر آگهی خودرو، صفحه اصلی محاسبه‌گر و فاکتورهای چاپی استفاده می‌شود.">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">شماره واتساپ امارات</label>
                    <input type="text" dir="ltr" name="whatsapp_uae_number" value="{{ old('whatsapp_uae_number', $whatsappUae) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left num-font dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">شماره واتساپ / موبایل ایران</label>
                    <input type="text" dir="ltr" name="whatsapp_iran_number" value="{{ old('whatsapp_iran_number', $whatsappIran) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left num-font dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">تلفن دفتر تهران</label>
                    <input type="text" dir="ltr" name="tehran_office_phone" value="{{ old('tehran_office_phone', $tehranOfficePhone) }}" required
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left num-font dark:border-white/10 dark:bg-white/5">
                </div>
            </div>
        </x-card>

        <x-card title="ربات‌های انتشار محتوا (تلگرام و بله)" icon="message" class="max-w-lg"
                subtitle="برای ساخت ربات: در تلگرام با @BotFather و در بله با @BaleBotFather یک ربات بسازید، آن را ادمین کانال خود کنید، و توکن + شناسه کانال (مثلاً @channel یا chat id عددی) را اینجا وارد کنید.">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">توکن ربات تلگرام</label>
                    <input type="text" dir="ltr" name="telegram_bot_token" value="{{ old('telegram_bot_token', $telegramBotToken) }}"
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">شناسه کانال تلگرام</label>
                    <input type="text" dir="ltr" name="telegram_chat_id" value="{{ old('telegram_chat_id', $telegramChatId) }}" placeholder="@my_channel"
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">توکن ربات بله</label>
                    <input type="text" dir="ltr" name="bale_bot_token" value="{{ old('bale_bot_token', $baleBotToken) }}"
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left dark:border-white/10 dark:bg-white/5">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-ink-500">شناسه کانال بله</label>
                    <input type="text" dir="ltr" name="bale_chat_id" value="{{ old('bale_chat_id', $baleChatId) }}"
                           class="w-full rounded-xl border border-ink-200 bg-ink-50 px-3.5 py-2.5 text-sm text-left dark:border-white/10 dark:bg-white/5">
                </div>
                <p class="text-[11px] text-ink-400">واتساپ API رایگان و رسمی برای ارسال خودکار ندارد؛ دکمه واتساپ در صفحه هر آگهی/مطلب فقط متن آماده را در واتساپ باز می‌کند تا عکس را دستی پیوست و ارسال کنید.</p>
            </div>
        </x-card>

        <x-button type="submit" variant="amber" size="lg">
            <x-icon name="check" class="w-4 h-4" /> ذخیره همه تنظیمات
        </x-button>
    </form>
</x-layouts.admin>

