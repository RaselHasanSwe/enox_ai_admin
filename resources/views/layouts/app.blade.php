<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>EnoX | @yield('title', 'Admin')</title>

    <script>
        (function () {
            var saved = localStorage.getItem('enox-admin-dark');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = saved !== null ? saved === 'true' : prefersDark;
            if (isDark) document.documentElement.classList.add('dark');
            if (localStorage.getItem('enox-sidebar') === 'small') {
                document.documentElement.classList.add('sidebar-small-layout');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(session('enox_access_token'))
        <script>
            window.ENOX_ADMIN = {
                apiUrl: @json(rtrim(config('enox.api_url'), '/')),
                wsUrl: @json(rtrim(config('enox.ws_url'), '/')),
                token: @json(session('enox_access_token')),
                queueUrl: @json(route('handoff.queue')),
                productCdn: @json(rtrim(config('enox.product_cdn'), '/')),
                agentName: @json(session('enox_user.name')),
            };
        </script>
    @endif
    @stack('css')
</head>
<body class="h-screen overflow-hidden bg-slate-100 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-200">
    <div class="flex h-screen overflow-hidden">
        @include('layouts.sidebar')

        <div class="flex-1 flex flex-col h-full overflow-hidden min-w-0">
            @include('layouts.topbar')

            <main class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden" id="mainContent">
                @yield('content')
            </main>

            @include('layouts.footer')
        </div>
    </div>

    <div id="sidebarBackdrop" onclick="closeSidebar()" class="hidden fixed inset-0 bg-black/40 z-[200] lg:hidden"></div>
    @stack('js')
</body>
</html>
