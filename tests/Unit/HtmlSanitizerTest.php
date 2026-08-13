<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_removes_active_content_and_preserves_allowed_persian_markup(): void
    {
        $dirty = '<script>alert(1)</script><p onclick="evil()">متن فارسی <strong>امن</strong></p>'
            .'<img src=x onerror="evil()"><a href="javascript:alert(1)">بد</a>'
            .'<a href="jav&#x61;script:alert(2)" target="_blank">بد دوم</a>'
            .'<ul><li>مورد مجاز</li></ul>';

        $clean = (new HtmlSanitizer)->clean($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', strtolower($clean));
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringContainsString('متن فارسی <strong>امن</strong>', $clean);
        $this->assertStringContainsString('<ul><li>مورد مجاز</li></ul>', $clean);
    }
}
