<?php

namespace App\Services\Capture;

interface ListingCaptureSource
{
    /** @return array{status:string, html:?string, url:string, warnings:array<int,string>} */
    public function capture(string $url, array $payload = []): array;
}

