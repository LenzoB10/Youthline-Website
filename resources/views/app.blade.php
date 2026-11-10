<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $seo = data_get($page, 'props.seo', []);
            $siteName = config('app.name', 'Laravel');
            $title = $seo['full_title'] ?? $siteName;
            $description = $seo['description'] ?? null;
            $canonical = $seo['canonical'] ?? url()->current();
            $ogImage = $seo['image'] ?? asset('imgs/og-image.jpg');
            $ogType = $seo['type'] ?? 'website';
            $robots = $seo['robots'] ?? 'index, follow';
        @endphp

        <title data-inertia>{{ $title }}</title>
        @if ($description)
            <meta name="description" content="{{ $description }}">
        @endif
        <meta name="robots" content="{{ $robots }}">
        <meta name="theme-color" content="#1c7d3c">
        <link rel="canonical" href="{{ $canonical }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
        <link rel="icon" type="image/png" href="{{ asset('favicon-32.png') }}" sizes="32x32">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        {{-- Open Graph --}}
        <meta property="og:site_name" content="{{ $seo['site_name'] ?? $siteName }}">
        <meta property="og:title" content="{{ $seo['title'] ?? $title }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:url" content="{{ $canonical }}">
        <meta property="og:image" content="{{ $ogImage }}">

        {{-- Twitter --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] ?? $title }}">
        @if ($description)
            <meta name="twitter:description" content="{{ $description }}">
        @endif
        <meta name="twitter:image" content="{{ $ogImage }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased ">
        @inertia
    </body>
</html>
