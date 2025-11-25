<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
        <meta name="description" content="Dilo Records - Plataforma de Distribución y Gestión de Música. Descubre artistas emergentes, releases y eventos musicales.">
        <meta name="keywords" content="música, artistas, releases, eventos, distribución musical, plataforma musical">
        <meta name="author" content="Dilo Records">
        <meta name="creator" content="Dilo Records">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="language" content="{{ app()->getLocale() }}">
        <meta name="revisit-after" content="7 days">
        
        <!-- Preconnect para optimización de fuentes y recursos externos -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="preconnect" href="https://ik.imagekit.io" crossorigin>
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
        
        <!-- Canonical URL -->
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ config('app.name', 'Dilo Records') }} - Plataforma Musical">
        <meta property="og:description" content="Descubre artistas emergentes y la mejor música en Dilo Records. Distribución, eventos y releases en un solo lugar.">
        <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="image/jpeg">
        <meta property="og:locale" content="{{ app()->getLocale() }}">
        <meta property="og:site_name" content="{{ config('app.name', 'Dilo Records') }}">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="{{ url()->current() }}">
        <meta name="twitter:title" content="{{ config('app.name', 'Dilo Records') }} - Plataforma Musical">
        <meta name="twitter:description" content="Descubre artistas emergentes y la mejor música en Dilo Records.">
        <meta name="twitter:image" content="{{ asset('images/twitter-image.jpg') }}">
        <meta name="twitter:creator" content="@dilorecords">
        <meta name="twitter:site" content="@dilorecords">

        <!-- Additional Meta Tags for Social Sharing -->
        <meta property="og:image:alt" content="Dilo Records - Plataforma de Música">

        <!-- Title for Inertia (será reemplazado dinámicamente) -->
        <title inertia>{{ config('app.name', 'Dilo Records') }}</title>

        <!-- Fonts with font-display=swap for better performance -->
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|roboto:400,500,600,700&display=swap" rel="stylesheet" />
        
        <!-- Font Awesome (async loading) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

        <!-- Security & Performance Headers (served via server configuration) -->
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        @if(app()->environment('local'))
            <!-- CSP permisivo en local para desarrollo con Vite -->
            <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: http://localhost:5173; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: http://localhost:5173; style-src 'self' 'unsafe-inline' https: http://localhost:5173;">
        @else
            <!-- CSP restrictivo en producción -->
            <meta http-equiv="Content-Security-Policy" content="default-src 'self' https: data:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:; style-src 'self' 'unsafe-inline' https:;">
        @endif

        <!-- Structured Data (JSON-LD) for SEO -->
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "Organization",
            "name": "{{ config('app.name', 'Dilo Records') }}",
            "url": "{{ url('/') }}",
            "logo": "{{ asset('images/logo.png') }}",
            "description": "Plataforma de distribución y gestión de música, artistas emergentes, releases y eventos musicales",
            "sameAs": [
                "https://www.spotify.com/dilorecords",
                "https://www.instagram.com/dilorecords",
                "https://www.twitter.com/dilorecords"
            ],
            "contactPoint": {
                "@@type": "ContactPoint",
                "contactType": "Customer Support",
                "email": "{{ env('MAIL_FROM_ADDRESS', 'support@dilorecords.com') }}"
            }
        }
        </script>

        <!-- Apple & Android Meta Tags -->
        <meta name="theme-color" content="#ffa236">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Dilo Records') }}">
        <meta name="msapplication-TileColor" content="#ffa236">
        <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">

        <!-- Favicon & Icons -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">

        <!-- Performance: Prefetch & Preload critical resources -->
        <link rel="prefetch" href="{{ mix('js/app.js') }}">
        <link rel="preload" href="{{ mix('css/app.css') }}" as="style">

        <!-- Scripts and Vite Integration -->
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-black text-white" data-page-view>
        @inertia

        <!-- Noscript fallback -->
        <noscript>
            <p style="padding: 20px; text-align: center; color: white;">
                {{ config('app.name', 'Dilo Records') }} requiere JavaScript habilitado para funcionar correctamente.
            </p>
        </noscript>

        <!-- Structured Data for WebSite Search -->
        <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "WebSite",
            "name": "{{ config('app.name', 'Dilo Records') }}",
            "url": "{{ url('/') }}",
            "potentialAction": {
                "@@type": "SearchAction",
                "target": {
                    "@@type": "EntryPoint",
                    "urlTemplate": "{{ url('artistas') }}?search={search_term_string}"
                },
                "query-input": "required name=search_term_string"
            }
        }
        </script>

        <!-- Tracking & Analytics (add your tracking code here) -->
        {{-- <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script> --}}
        {{-- <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'GA_MEASUREMENT_ID');
        </script> --}}
    </body>
</html>