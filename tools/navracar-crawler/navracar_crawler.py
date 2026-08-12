#!/usr/bin/env python3
"""
navracar_crawler.py — ابزار دسکتاپ جمع‌آوری آگهی از دابیزل برای ناوراکار.

این اسکریپت روی سیستم شما (نه سرور) اجرا می‌شود تا با آی‌پی/مرورگر واقعی
شما به دابیزل درخواست بزند — احتمال بلاک‌شدن به‌مراتب کمتر از سرور است.
خروجی یک فایل JSON است که دقیقاً با فرمت «ایمپورت گروهی» پنل مدیریت
ناوراکار سازگار است (آدرس: پنل مدیریت ← آگهی‌های دابیزل ← ایمپورت گروهی).

نحوه اجرا (پایتون نصب‌شده لازم است — راهنمای کامل در README.md):
    python navracar_crawler.py --config config.json

یا بعد از ساخت نسخهٔ اجرایی ویندوز (به README.md مراجعه کنید):
    navracar_crawler.exe --config config.json

--------------------------------------------------------------------------
منطق استخراج فیلدها عیناً از app/Services/DubizzleParser.php (سمت سایت)
پورت شده تا هر دو مسیر (لینک تکی در پنل، و این کرالر) نتیجهٔ یکسان بدهند.
--------------------------------------------------------------------------
"""

from __future__ import annotations

import argparse
import html
import json
import random
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from dataclasses import dataclass, field
from pathlib import Path

USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0",
]

OVERVIEW_FIELDS = [
    "body_type", "doors", "engine_capacity_cc", "exterior_color", "fuel_type",
    "horsepower", "interior_color", "motors_trim", "no_of_cylinders",
    "seating_capacity", "seller_type", "target_market", "transmission_type", "warranty",
]


# ---------------------------------------------------------------------------
# پیمایش «مثل کاربر واقعی»: تأخیر تصادفی بین درخواست‌ها + مکث طولانی‌تر هر
# چند درخواست یک‌بار، دقیقاً همان چیزی که برای دور زدن تشخیص ربات لازم است.
# این مقادیر را از config.json می‌توان تنظیم کرد.
# ---------------------------------------------------------------------------
@dataclass
class Pacing:
    min_delay: float = 3.0
    max_delay: float = 8.0
    long_pause_every: int = 15
    long_pause_seconds: float = 45.0
    _request_count: int = field(default=0, init=False)

    def wait(self) -> None:
        self._request_count += 1
        if self.long_pause_every and self._request_count % self.long_pause_every == 0:
            pause = self.long_pause_seconds * (0.8 + random.random() * 0.4)
            print(f"  … مکث طولانی‌تر برای شبیه‌سازی رفتار انسانی: {pause:.0f} ثانیه", file=sys.stderr)
            time.sleep(pause)
        else:
            time.sleep(random.uniform(self.min_delay, self.max_delay))


def fetch(url: str, timeout: int = 25) -> str | None:
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": random.choice(USER_AGENTS),
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.9",
            "Referer": "https://dubai.dubizzle.com/",
        },
    )
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            charset = resp.headers.get_content_charset() or "utf-8"
            return resp.read().decode(charset, errors="replace")
    except (urllib.error.URLError, urllib.error.HTTPError, TimeoutError) as e:
        print(f"  ✗ خطا در دریافت {url}: {e}", file=sys.stderr)
        return None


# ---------------------------------------------------------------------------
# استخراج فیلدها — پورت مستقیم از DubizzleParser.php
# ---------------------------------------------------------------------------
def extract_simple(html_text: str, testid: str) -> str | None:
    pattern = re.compile(r'data-testid="' + re.escape(testid) + r'"[^>]*>([^<]*)<')
    m = pattern.search(html_text)
    if not m:
        return None
    value = html.unescape(m.group(1)).strip()
    return value or None


def extract_window(html_text: str, testid: str, length: int = 400) -> str | None:
    marker = f'data-testid="{testid}"'
    pos = html_text.find(marker)
    if pos == -1:
        return None
    gt = html_text.find(">", pos)
    if gt == -1:
        return None
    return html_text[gt + 1: gt + 1 + length]


def clean_fragment(fragment: str) -> str:
    fragment = re.sub(r"<!--.*?-->", "", fragment, flags=re.S)
    fragment = re.sub(r"<br\s*/?>", "\n", fragment, flags=re.I)
    fragment = re.sub(r"<button[^>]*>.*?</button>", "", fragment, flags=re.I | re.S)
    fragment = re.sub(r"<[^>]+>", "", fragment)
    return html.unescape(fragment).strip()


def extract_price(html_text: str) -> float | None:
    window = extract_window(html_text, "listing-price", 250)
    if window is None:
        return None
    clean = clean_fragment(window)
    m = re.search(r"([\d,]+)", clean)
    if not m:
        return None
    return float(m.group(1).replace(",", ""))


def extract_posted_on(html_text: str) -> str | None:
    window = extract_window(html_text, "posted-on", 200)
    if window is None:
        return None
    clean = clean_fragment(window)
    clean = re.sub(r"^posted on\s*:?\s*", "", clean, flags=re.I)
    return clean.strip() or None


def extract_description(html_text: str) -> str | None:
    window = extract_window(html_text, "description", 10000)
    if window is None:
        return None
    end_markers = ['data-testid="show-number"', 'data-testid="report-this-ad"']
    cut = len(window)
    for marker in end_markers:
        p = window.find(marker)
        if p != -1 and p < cut:
            cut = p
    cleaned = clean_fragment(window[:cut])
    return cleaned or None


def extract_images(html_text: str) -> list[str]:
    urls = re.findall(r"https://dbz-images\.dubizzle\.com/images/[^\"'\s]+", html_text, flags=re.I)
    seen: dict[str, bool] = {}
    out: list[str] = []
    for raw in urls:
        base = re.sub(r"\?.*$", "", raw)
        if base in seen:
            continue
        seen[base] = True
        out.append(base + "?impolicy=dpv")
    return out


def extract_from_url(url: str) -> dict:
    result = {"make": None, "model": None, "model_year": None}
    m = re.search(r"/motors/(?:used-cars|new-cars|export-cars)/([a-z0-9-]+)/([a-z0-9-]+)/(\d{4})/", url, re.I)
    if m:
        result["make"], result["model"], result["model_year"] = m.group(1), m.group(2), m.group(3)
    return result


def parse_listing(html_text: str, url: str) -> dict:
    data = extract_from_url(url)
    data["source_url"] = url
    data["title_en"] = extract_simple(html_text, "listing-name")
    data["sub_heading"] = extract_simple(html_text, "listing-sub-heading")
    data["price_aed"] = extract_price(html_text)
    year = extract_simple(html_text, "listing-year-value")
    if year:
        data["model_year"] = year
    data["kilometers"] = extract_simple(html_text, "listing-kilometers-value")
    data["regional_specs"] = extract_simple(html_text, "listing-regional_specs-value")
    data["steering_side"] = extract_simple(html_text, "listing-steering_side-value")
    data["location_text"] = extract_simple(html_text, "listing-location-map")
    data["posted_on_dubizzle"] = extract_posted_on(html_text)
    data["description_en"] = extract_description(html_text)

    for f in OVERVIEW_FIELDS:
        data[f] = extract_simple(html_text, f"overview-{f}-value")
    data["trim_level"] = data.pop("motors_trim", None)

    data["images"] = extract_images(html_text)
    return data


# ---------------------------------------------------------------------------
# پیمایش صفحهٔ دسته/جستجو برای یافتن لینک تک‌تک آگهی‌ها
# ---------------------------------------------------------------------------
def extract_listing_links(html_text: str, base_url: str) -> list[str]:
    hrefs = re.findall(r'href="(/motors/used-cars/[a-z0-9\-/]+-[a-f0-9]{32}/?)"', html_text, flags=re.I)
    seen: dict[str, bool] = {}
    out: list[str] = []
    for href in hrefs:
        full = urllib.parse.urljoin(base_url, href)
        if full in seen:
            continue
        seen[full] = True
        out.append(full)
    return out


def paginate_url(url: str, page: int) -> str:
    parsed = urllib.parse.urlsplit(url)
    query = dict(urllib.parse.parse_qsl(parsed.query))
    query["page"] = str(page)
    return urllib.parse.urlunsplit(parsed._replace(query=urllib.parse.urlencode(query)))


# ---------------------------------------------------------------------------
# اجرای اصلی
# ---------------------------------------------------------------------------
def crawl(config: dict) -> list[dict]:
    pacing = Pacing(**config.get("pacing", {}))
    categories: list[dict] = config["categories"]
    max_listings_per_category = config.get("max_listings_per_category", 40)
    max_pages_per_category = config.get("max_pages_per_category", 5)

    all_listing_urls: list[str] = []
    for cat in categories:
        cat_url = cat["url"]
        cat_name = cat.get("name", cat_url)
        print(f"\n=== دسته: {cat_name} ===")
        collected: list[str] = []
        for page in range(1, max_pages_per_category + 1):
            page_url = cat_url if page == 1 else paginate_url(cat_url, page)
            print(f"  صفحه {page}: {page_url}")
            pacing.wait()
            body = fetch(page_url)
            if not body:
                break
            links = extract_listing_links(body, page_url)
            if not links:
                print("  آگهی جدیدی در این صفحه پیدا نشد — پایان این دسته.")
                break
            new_links = [l for l in links if l not in collected]
            collected.extend(new_links)
            print(f"  {len(new_links)} آگهی جدید پیدا شد (مجموع: {len(collected)})")
            if len(collected) >= max_listings_per_category:
                break
        all_listing_urls.extend(collected[:max_listings_per_category])

    print(f"\n>>> مجموع {len(all_listing_urls)} آگهی برای دریافت جزئیات یافت شد.\n")

    results: list[dict] = []
    for i, url in enumerate(all_listing_urls, start=1):
        print(f"[{i}/{len(all_listing_urls)}] {url}")
        pacing.wait()
        body = fetch(url)
        if not body:
            continue
        try:
            row = parse_listing(body, url)
        except Exception as e:  # noqa: BLE001 — یک آگهی خراب نباید کل کرالر را متوقف کند
            print(f"  ✗ خطا در پردازش: {e}", file=sys.stderr)
            continue
        if not row.get("title_en") and not row.get("price_aed"):
            print("  ✗ استخراج ناموفق (ساختار صفحه ممکن است تغییر کرده باشد) — رد شد.")
            continue
        results.append(row)
        print(f"  ✓ {row.get('title_en')} — {row.get('price_aed')} AED — {len(row.get('images', []))} عکس")

    return results


def main() -> None:
    parser = argparse.ArgumentParser(description="کرالر دسکتاپ ناوراکار برای دابیزل")
    parser.add_argument("--config", default="config.json", help="مسیر فایل تنظیمات (پیش‌فرض: config.json)")
    parser.add_argument("--output", default=None, help="مسیر فایل خروجی JSON (پیش‌فرض: خودکار با تاریخ/ساعت)")
    args = parser.parse_args()

    config_path = Path(args.config)
    if not config_path.exists():
        print(f"فایل تنظیمات پیدا نشد: {config_path}", file=sys.stderr)
        print("از روی config.example.json یک کپی با نام config.json بسازید و دسته‌های دلخواه را وارد کنید.", file=sys.stderr)
        sys.exit(1)

    config = json.loads(config_path.read_text(encoding="utf-8"))

    output_path = Path(args.output) if args.output else Path(f"navracar-import-{time.strftime('%Y%m%d-%H%M%S')}.json")

    results = crawl(config)

    output_path.write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"\n>>> {len(results)} آگهی در فایل زیر ذخیره شد:\n{output_path.resolve()}")
    print(">>> این فایل را در پنل مدیریت ناوراکار ← آگهی‌های دابیزل ← «ایمپورت گروهی از فایل کرالر» آپلود کنید.")


if __name__ == "__main__":
    main()
