<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionalAuthSanctum
{
    /**
     * Обрабатывает входящий запрос.
     *
     * Если в запросе присутствуют аутентификационные данные, пытаемся аутентифицировать
     * пользователя через guard "sanctum". Если аутентификация не проходит, запрос
     * продолжится с $request->user() равным null.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->headers->has('Authorization')) {
            Auth::shouldUse('sanctum');
            $user = Auth::guard('sanctum')->user();
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
        }

        return $next($request);
    }
}
