<?php

use App\Services\AuditService;

if (! function_exists('audit')) {
    function audit(): AuditService
    {
        return app(AuditService::class);
    }
}
