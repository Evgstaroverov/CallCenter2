<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Для API-запросов НЕ делаем редирект
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        // Для web-запросов — стандартное поведение
        return route('login');
    }
}
