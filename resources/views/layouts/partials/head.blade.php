<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'OmahIoT Dashboard')</title>
{{-- Favicon / Browser Tab Icon --}}
<link rel="icon" type="image/png" href="{{ asset('images/logo_omahiot.svg') }}">
<link rel="apple-touch-icon" href="{{ asset('images/logo_omahiot.svg') }}">
{{-- Sidebar terbuka secara default; pilihan terakhir diingat per browser. Diterapkan sebelum render agar tidak berkedip. --}}
<script>
    try { if (localStorage.getItem('sidebar') === 'closed') document.documentElement.classList.add('sb-collapsed'); } catch (e) {}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
@if ($withVite ?? true)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
