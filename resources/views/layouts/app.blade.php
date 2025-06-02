{{-- resources/views/layouts/app.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'MikroTik Monitor')</title>
  <link rel="stylesheet" href="{{ mix('css/app.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 text-gray-900">

  {{-- ─── Navbar ───────────────────────────────────────────────────────── --}}
  <nav class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center">
      {{-- Branding --}}
      <a href="{{ route('dashboard') }}" class="text-xl font-bold text-indigo-700">
        MikroTik Monitor
      </a>

      <div class="flex-1"></div>

      {{-- Dashboard Link --}}
      <a href="{{ route('dashboard') }}"
         class="{{ request()->routeIs('dashboard')
                     ? 'text-indigo-700 font-semibold'
                     : 'text-gray-700 hover:text-indigo-600' }} text-sm">
        Dashboard
      </a>

      <span class="mx-4 text-gray-300">|</span>

      {{-- Routers Link --}}
      <a href="{{ route('routers.index') }}"
         class="{{ request()->routeIs('routers.*')
                     ? 'text-indigo-700 font-semibold'
                     : 'text-gray-700 hover:text-indigo-600' }} text-sm">
        Routers
      </a>
    </div>
  </nav>

  {{-- ─── Page Header ───────────────────────────────────────────────────── --}}
  <header class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    @yield('header')
  </header>

  {{-- ─── Main Content ───────────────────────────────────────────────────── --}}
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    @yield('content')
  </main>

  <script src="{{ mix('js/app.js') }}"></script>
  @stack('scripts')


  {{-- ─── Global Helper: formatBytes() ──────────────────────────────────── --}}
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

</body>
</html>
