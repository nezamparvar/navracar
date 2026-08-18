<?php

namespace App\Services;

final readonly class FcmSendResult
{
    public function __construct(
        public bool $successful,
        public bool $invalidToken = false,
        public ?string $errorCode = null,
    ) {}
}
