<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevenir clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevenir MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Habilitar XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content Security Policy - condicional según ambiente
        if (app()->environment('local')) {
            // En desarrollo, permitir localhost:5173 para Vite + HMR + Assets
            $csp = implode('; ', [
                "default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: http://localhost:5173",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: http://localhost:5173",
                "style-src 'self' 'unsafe-inline' https: http://localhost:5173",
                "img-src 'self' https: data: http://localhost:5173",
                "font-src 'self' https: data: http://localhost:5173",
                "connect-src 'self' https: ws://localhost:5173 wss://localhost:5173 http://localhost:5173",
                "media-src 'self' https: http://localhost:5173",
                "object-src 'none'",
                "frame-ancestors 'self'",
            ]);
        } else {
            // En producción, CSP restrictivo
            $csp = implode('; ', [
                "default-src 'self' https: data:",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:",
                "style-src 'self' 'unsafe-inline' https:",
                "img-src 'self' https: data:",
                "font-src 'self' https: data:",
                "connect-src 'self' https:",
                "media-src 'self' https:",
                "object-src 'none'",
                "frame-ancestors 'self'",
            ]);
        }
        $response->headers->set('Content-Security-Policy', $csp);

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Feature Policy / Permissions Policy
        $response->headers->set('Permissions-Policy', implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
        ]));

        // HSTS (HTTP Strict Transport Security) - solo en producción
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Disable caching for sensitive pages
        if ($request->routeIs('admin.*', 'dashboard')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
