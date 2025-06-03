<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', 'MikroTik Monitor')</title>
  <link rel="stylesheet" href="{{ mix('css/app.css') }}" />
  {{-- Load Chart.js so child scripts can call new Chart(...) --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 text-gray-900">

  {{-- ─── Navbar ───────────────────────────────────────────────────────── --}}
  <nav class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center">
      <a href="{{ route('dashboard') }}" class="text-xl font-bold text-indigo-700">
        MikroTik Monitor
      </a>
      <div class="flex-1"></div>
      <a href="{{ route('dashboard') }}"
         class="{{ request()->routeIs('dashboard') ? 'text-indigo-700 font-semibold' : 'text-gray-700 hover:text-indigo-600' }} text-sm">
        Dashboard
      </a>
      <span class="mx-4 text-gray-300">|</span>
      <a href="{{ route('routers.index') }}"
         class="{{ request()->routeIs('routers.*') ? 'text-indigo-700 font-semibold' : 'text-gray-700 hover:text-indigo-600' }} text-sm">
        Routers
      </a>
    </div>
  </nav>

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- 1) Define formatBytes() helper *before* any child view is rendered  --}}
  @php
    if (! function_exists('formatBytes')) {
      /**
       * Convert raw bytes into a human-readable string.
       */
      function formatBytes($bytes, $precision = 2) {
        if (empty($bytes) || $bytes <= 0) {
          return '0 Bytes';
        }
        $units = ['Bytes','KB','MB','GB','TB'];
        $base  = log($bytes) / log(1024);
        $idx   = (int) floor($base);
        return round(pow(1024, $base - $idx), $precision) . ' ' . $units[$idx];
      }
    }
  @endphp

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- 2) Page Header Section --}}
  <header class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    @yield('header')
  </header>

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- 3) Main Content Section --}}
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    @yield('content')
  </main>

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- 4) App.js + any pushed <script> --}}
  <script src="{{ mix('js/app.js') }}"></script>
  @stack('scripts')
</body>
</html>
