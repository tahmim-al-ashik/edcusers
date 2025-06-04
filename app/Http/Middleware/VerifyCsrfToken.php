<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/api/shared/form-submit/*',
        '/api/import/*',
        '/api/export/*',
        '/api/v2/panel/import/*',
        '/pay',
        '/success',
        '/cancel',
        '/fail',
        '/ipn',
        '/hotspot/pay',
        '/hotspot/success',
        '/hotspot/cancel',
        '/hotspot/fail',
        '/hotspot/ipn'
    ];
}
