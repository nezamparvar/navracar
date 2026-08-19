<!DOCTYPE html>
<html lang="{{ $locale ?? 'fa' }}" dir="{{ ($locale ?? 'fa') === 'en' ? 'ltr' : 'rtl' }}">
<head>
<meta charset="UTF-8">
<title>{{ $docTitle }} {{ $docNumber }}</title>
<style>
    @font-face {
        font-family: 'Vazir';
        src: url('{{ $fontRegular }}') format('truetype');
        font-weight: normal;
    }
    @font-face {
        font-family: 'Vazir';
        src: url('{{ $fontBold }}') format('truetype');
        font-weight: bold;
    }
    * { box-sizing: border-box; }
    body {
        font-family: 'Vazir', sans-serif;
        direction: {{ ($locale ?? 'fa') === 'en' ? 'ltr' : 'rtl' }};
        text-align: {{ ($locale ?? 'fa') === 'en' ? 'left' : 'right' }};
        color: #1A1730;
        font-size: 11px;
        line-height: 1.6;
        margin: 0;
    }
    .header {
        background-color: #1E1B3A;
        color: #ffffff;
        padding: 12px 26px;
        width: 100%;
    }
    .header table { width: 100%; border-collapse: collapse; }
    .header .brand { font-size: 18px; font-weight: bold; }
    .header .sub { font-size: 10px; color: #C9D6FF; margin-top: 3px; }
    .header .meta { font-size: 10px; text-align: left; line-height: 1.8; }
    .header .meta b { color: #E8C766; }
    .body { padding: 10px 26px; }
    .info-box {
        background-color: #F3F0FA;
        border-radius: 6px;
        padding: 8px 16px;
        margin-bottom: 10px;
        width: 100%;
    }
    .info-box table { width: 100%; border-collapse: collapse; }
    .info-box td { padding: 3px 0; font-size: 10px; vertical-align: top; }
    .info-box .label { color: #6B6584; font-size: 9px; display: block; }
    h3.section-title {
        font-size: 12px;
        color: #2D2657;
        border-bottom: 2px solid #EDE9F7;
        padding-bottom: 4px;
        margin: 10px 0 5px;
    }
    table.rows { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
    table.rows th {
        background-color: #F7F5F1;
        color: #6B6584;
        font-size: 9px;
        padding: 4px 8px;
        text-align: right;
        border-bottom: 1px solid #E4DFD3;
    }
    table.rows td {
        padding: 4px 8px;
        font-size: 10px;
        border-bottom: 1px solid #EDEAE2;
    }
    table.rows td.amount { text-align: left; font-weight: bold; white-space: nowrap; }
    table.rows td.rate { color: #6B6584; font-size: 9px; }
    table.totals { width: 100%; border-collapse: collapse; margin-top: 3px; }
    table.totals td { padding: 5px 10px; font-size: 11px; }
    table.totals td.amount { text-align: left; font-weight: bold; white-space: nowrap; }
    table.totals tr.grand { background-color: #F7EFD2; }
    table.totals tr.grand td { font-size: 13px; font-weight: bold; color: #171433; }
    .footer-note {
        margin-top: 5px;
        padding-top: 4px;
        border-top: 1px dashed #E4DFD3;
        font-size: 7px;
        color: #8A8499;
        line-height: 1.35;
        page-break-inside: avoid;
    }
    .contact-box {
        margin-top: 6px;
        background-color: #F7F5F1;
        border-radius: 6px;
        padding: 6px 16px;
        font-size: 9px;
        line-height: 1.4;
    }
</style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td class="brand">
                    ناوراکار
                    <div class="sub">{{ $docTitle }}</div>
                </td>
                <td class="meta">
                    شماره: <b>{{ $docNumber }}</b><br>
                    تاریخ صدور: {{ $docDate }}<br>
                    @if($validUntil)اعتبار تا: <b>{{ $validUntil }}</b><br>@endif
                </td>
            </tr>
        </table>
    </div>

    <div class="body">
        <div class="info-box">
            <table>
                <tr>
                    <td style="width:50%">
                        <span class="label">نام مشتری</span>
                        {{ $customerName }}
                    </td>
                    <td style="width:50%">
                        <span class="label">شماره تماس</span>
                        {{ $customerPhone }}
                    </td>
                </tr>
                @if($customerEmail || $carLabel)
                <tr>
                    @if($customerEmail)
                    <td>
                        <span class="label">ایمیل</span>
                        {{ $customerEmail }}
                    </td>
                    @endif
                    @if($carLabel)
                    <td>
                        <span class="label">خودرو</span>
                        {{ $carLabel }}{{ $categoryLabel ? ' — '.$categoryLabel : '' }}
                    </td>
                    @endif
                </tr>
                @endif
            </table>
        </div>

        <h3 class="section-title">تفکیک هزینه‌ها (واحد: تومان)</h3>
        <table class="rows">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:45%">شرح</th>
                    <th style="width:30%">نرخ / توضیح</th>
                    <th style="width:20%">مبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['label'] ?? '' }}</td>
                    <td class="rate">{{ $row['rate'] ?? '' }}</td>
                    <td class="amount">{{ $row['amount'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <h3 class="section-title">جمع‌بندی</h3>
        <table class="totals">
            @foreach($totalsSummary as $row)
                <tr @if(!empty($row['emphasis'])) class="grand" @endif>
                    <td>{{ $row['label'] }}</td>
                    <td class="amount">{{ $row['amount'] }}</td>
                </tr>
            @endforeach
        </table>

        <div class="contact-box">
            <b>ارتباط با ما</b><br>
            ایران: {{ $contact['iran'] }} (واتس‌اپ | بله | تلگرام)<br>
            امارات: {{ $contact['uae'] }} (واتس‌اپ | تلگرام)<br>
            دفتر تهران: {{ $contact['tehran'] }}<br>
            وب‌سایت: navracar.com
        </div>

        <div class="footer-note">
            {{ $footerNote }}
        </div>
    </div>

</body>
</html>

