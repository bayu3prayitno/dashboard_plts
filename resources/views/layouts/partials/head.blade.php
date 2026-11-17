<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'OmahIoT Dashboard')</title>
{{-- Favicon / Browser Tab Icon --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('images/logo_omahiot.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('images/logo_omahiot.svg') }}">
{{-- Sidebar terbuka secara default; pilihan terakhir diingat per browser. Diterapkan sebelum render agar tidak berkedip. --}}
<script>
    try { if (localStorage.getItem('sidebar') === 'closed') document.documentElement.classList.add('sb-collapsed'); } catch (e) {}
</script>
{{-- Font: dimuat tanpa memblokir render (teks tampil dulu dengan font sistem, lalu berganti; display=swap) --}}
@php($fontUrl = 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="{{ $fontUrl }}">
<link rel="stylesheet" href="{{ $fontUrl }}" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="{{ $fontUrl }}"></noscript>
{{--
    Navigasi instan (Edge/Chrome): halaman tujuan di-prerender saat kursor diarahkan ~200 ms ke link,
    sehingga saat diklik langsung tampil. Browser lain mengabaikannya. Link unduhan/API/data-no-progress dikecualikan.
--}}
<script type="speculationrules">
{
    "prerender": [{
        "where": {
            "and": [
                { "href_matches": "/*" },
                { "not": { "href_matches": "/historical/export*" } },
                { "not": { "href_matches": "/api/*" } },
                { "not": { "href_matches": "/logout" } },
                { "not": { "selector_matches": "[data-no-progress], [download], [target=_blank]" } }
            ]
        },
        "eagerness": "moderate"
    }]
}
</script>
@if ($withVite ?? true)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
