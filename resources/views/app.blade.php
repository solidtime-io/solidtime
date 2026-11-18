<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

        <title inertia>{{ $whitelabel->getName() }}</title>

        <!-- Favicons -->
        @if($whitelabel->getFaviconUrl() !== null)
        <link rel="icon" href="{{ $whitelabel->getFaviconUrl() }}">
        <link rel="apple-touch-icon" href="{{ $whitelabel->getFaviconUrl() }}">
        @else
        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#000000">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="msapplication-TileColor" content="#000000">
        <meta name="msapplication-config" content="/favicons/browserconfig.xml">
        @endif
        <link rel="manifest" href="{{ route('web-manifest', absolute: false) }}">
        <meta name="theme-color" content="{{ $whitelabel->getThemeColor() }}">

        <!-- Scripts -->
        @routes
        @vite(\Nwidart\Modules\Module::getAssets())
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
