<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'api/break-start',
        'api/break-end',
        'api/login',
        'api/punch-out',
        'api/punch-in',
        'api/screenshot-upload',
        'api/idle-time'
    ];
}
