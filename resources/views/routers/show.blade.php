{{-- resources/views/routers/show.blade.php --}}

@php
    // ─── Guarantee formatBytes() exists before any usage ─────────────────────────────
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

@extends('layouts.app')

@section('title', "Router: {$router->name}")

{{-- Page header --}}
@section('header')
  <div class="flex justify-between items-center">
    <h2 class="text-2xl font-semibold text-gray-800">Router Details: {{ $router->name }}</h2>
    <a href="{{ route('routers.index') }}"
       class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold 
              rounded-md shadow transition">
      <i class="fas fa-arrow-left mr-2"></i> Back to List
    </a>
  </div>
@endsection

{{-- Main content --}}
@section('content')
<div class="space-y-6">

  {{-- ──────────────── Status Card ──────────────── --}}
  <div class="card">
    <div class="card-header flex justify-between items-center">
      <h3 class="text-lg font-medium text-gray-800">Status</h3>
      @if($router->latestStatus && $router->latestStatus->online)
        <span class="inline-flex items-center px-3 py-1 text-sm font-medium bg-green-100 text-green-700 rounded-full">
          <i class="fas fa-check-circle mr-1"></i> ONLINE
        </span>
      @else
        <span class="inline-flex items-center px-3 py-1 text-sm font-medium bg-red-100 text-red-700 rounded-full">
          <i class="fas fa-times-circle mr-1"></i> OFFLINE
        </span>
      @endif
    </div>
    <div class="card-body">
      @if($router->latestStatus && $router->latestStatus->online)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="p-4 bg-blue-50 rounded">
            <h4 class="text-sm font-semibold text-blue-800">CPU Load</h4>
            <p class="text-lg font-bold">{{ $router->latestStatus->cpu_load }}%</p>
          </div>
          <div class="p-4 bg-purple-50 rounded">
            <h4 class="text-sm font-semibold text-purple-800">Memory Usage</h4>
            <p class="text-lg font-bold">{{ round($router->latestStatus->memory_usage, 1) }}%</p>
          </div>
          <div class="p-4 bg-pink-50 rounded">
            <h4 class="text-sm font-semibold text-pink-800">Current Bandwidth</h4>
            <p class="text-sm text-blue-600">
              In: {{ formatBytes($router->latestStatus->total_bytes_in) }}
            </p>
            <p class="text-sm text-pink-600">
              Out: {{ formatBytes($router->latestStatus->total_bytes_out) }}
            </p>
          </div>
        </div>
      @else
        <p class="text-sm text-gray-500 italic">
          Router is currently offline or has never been checked.
        </p>
      @endif
    </div>
  </div>

  {{-- ──────────────── Router Info Card ──────────────── --}}
  <div class="card">
    <div class="card-header">
      <h3 class="text-lg font-medium text-gray-800">Router Info</h3>
    </div>
    <div class="card-body">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-gray-700">
        <div><strong>IP:</strong> {{ $router->ip_address }}:{{ $router->port }}</div>
        <div><strong>Location:</strong> {{ $router->location ?? 'Not specified' }}</div>
        <div><strong>Username:</strong> {{ $router->username }}</div>
        <div><strong>Status:</strong> {{ $router->is_active ? 'Active' : 'Disabled' }}</div>

        {{-- ← Total number of connected devices }}
        <div>
          <strong>Total Devices:</strong>
          <span class="font-semibold">{{ $paginatedDevices->total() }}</span>
        </div>
      </div>
      @if($router->description)
        <p class="mt-4 text-sm text-gray-600">
          <strong>Description:</strong> {{ $router->description }}
        </p>
      @endif
    </div>
  </div>

  {{-- ──────────────── 7-Day Bandwidth Chart (AJAX) ──────────────── --}}
  <div class="card">
    <div class="card-header">
      <h3 class="text-lg font-medium text-gray-800">Bandwidth (Last 7 Days)</h3>
    </div>
    <div class="card-body">
      <canvas id="routerBwChart" height="120"></canvas>
    </div>
  </div>

  {{-- ──────────────── Dynamic + Running Interfaces (DR) ──────────────── --}}
  <div class="card">
    <div class="card-header">
      <h3 class="text-lg font-medium text-gray-800">Dynamic + Running Interfaces (DR)</h3>
    </div>
    <div class="card-body">
      @if(count($drInterfaces) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          @foreach($drInterfaces as $ifName)
            @php
              $totals  = $ifaceTotals[$ifName] ?? ['rx_bytes' => 0, 'tx_bytes' => 0];
              $rxBytes = intval($totals['rx_bytes']);
              $txBytes = intval($totals['tx_bytes']);
            @endphp
            <div class="p-4 bg-gray-50 rounded shadow-sm">
              <h4 class="text-md font-semibold text-gray-800">{{ $ifName }}</h4>
              <p class="text-sm text-gray-700">
                <span class="text-blue-600 font-semibold">
                  ↓ {{ $rxBytes ? formatBytes($rxBytes) : '0 Bytes' }}
                </span><br>
                <span class="text-pink-600 font-semibold">
                  ↑ {{ $txBytes ? formatBytes($txBytes) : '0 Bytes' }}
                </span>
              </p>
              <p class="mt-2 text-xs text-gray-500">
                Last checked: {{ optional($router->latestStatus)->logged_at?->diffForHumans() ?? '—' }}
              </p>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-sm text-gray-500">No “DR” interfaces found.</p>
      @endif
    </div>
  </div>

  {{-- ──────────────── Running Interfaces (R) ──────────────── --}}
  <div class="card">
    <div class="card-header">
      <h3 class="text-lg font-medium text-gray-800">Running Interfaces (R)</h3>
    </div>
    <div class="card-body">
      @if(count($rInterfaces) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          @foreach($rInterfaces as $ifName)
            @php
              $totals  = $ifaceTotals[$ifName] ?? ['rx_bytes' => 0, 'tx_bytes' => 0];
              $rxBytes = intval($totals['rx_bytes']);
              $txBytes = intval($totals['tx_bytes']);
            @endphp
            <div class="p-4 bg-gray-50 rounded shadow-sm">
              <h4 class="text-md font-semibold text-gray-800">{{ $ifName }}</h4>
              <p class="text-sm text-gray-700">
                <span class="text-blue-600 font-semibold">
                  ↓ {{ $rxBytes ? formatBytes($rxBytes) : '0 Bytes' }}
                </span><br>
                <span class="text-pink-600 font-semibold">
                  ↑ {{ $txBytes ? formatBytes($txBytes) : '0 Bytes' }}
                </span>
              </p>
              <p class="mt-2 text-xs text-gray-500">
                Last checked: {{ optional($router->latestStatus)->logged_at?->diffForHumans() ?? '—' }}
              </p>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-sm text-gray-500">No “R” interfaces found.</p>
      @endif
    </div>
  </div>

  {{-- ──────────────── Connected Devices (Paginated) ──────────────── --}}
  <div class="card">
    <div class="card-header flex justify-between items-center">
      <h3 class="text-lg font-medium text-gray-800">
        Connected Devices ({{ $paginatedDevices->total() }})
      </h3>
      <div class="space-x-2">
        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
          <i class="fas fa-wifi mr-1"></i> Active: 
          {{ $paginatedDevices->where('active', true)->count() }}
        </span>
        <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
          <i class="fas fa-user-slash mr-1"></i> Inactive: 
          {{ $paginatedDevices->where('active', false)->count() }}
        </span>
      </div>
    </div>
    <div class="card-body space-y-4">
      @if($paginatedDevices->isEmpty())
        <p class="text-sm text-gray-500">No devices found or router is offline.</p>
      @else
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-left text-gray-600">
            <thead class="bg-gray-100 text-xs text-gray-500 uppercase">
              <tr>
                <th class="px-4 py-2">Interface</th>
                <th class="px-4 py-2">Hostname (User)</th>
                <th class="px-4 py-2">IP Address</th>
                <th class="px-4 py-2">Bandwidth In/Out</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              @foreach($paginatedDevices as $device)
                @php
                  $displayName = $device->hostname ?: $device->mac_address;
                  $inBytes     = intval($device->bytes_in);
                  $outBytes    = intval($device->bytes_out);
                @endphp
                <tr>
                  <td class="px-4 py-2">{{ $device->interface }}</td>
                  <td class="px-4 py-2">
                    <div class="font-medium text-gray-900">{{ $displayName }}</div>
                    <div class="text-xs text-gray-500">{{ $device->mac_address }}</div>
                  </td>
                  <td class="px-4 py-2">{{ $device->ip_address }}</td>
                  <td class="px-4 py-2">
                    <span class="text-blue-600">
                      ↓ {{ $inBytes ? formatBytes($inBytes) : '0 Bytes' }}
                    </span><br>
                    <span class="text-pink-600">
                      ↑ {{ $outBytes ? formatBytes($outBytes) : '0 Bytes' }}
                    </span>
                  </td>
                  <td class="px-4 py-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium 
                        {{ $device->active 
                            ? 'bg-green-100 text-green-700' 
                            : 'bg-gray-100 text-gray-700' }} rounded-full">
                      {{ $device->active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="px-4 py-2">
                    <form 
                      action="{{ route('routers.devices.destroy', [$router, $device]) }}" 
                      method="POST"
                      onsubmit="return confirm('Remove this device?');"
                    >
                      @csrf
                      @method('DELETE')
                      <button 
                        type="submit"
                        class="inline-flex items-center px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200"
                      >
                        <i class="fas fa-trash-alt mr-1"></i> Remove
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Pagination Links for Devices --}}
        @if($paginatedDevices->hasPages())
          <div class="px-4 py-3 bg-gray-100 border-t border-gray-200">
            {{ $paginatedDevices->links() }}
          </div>
        @endif
      @endif
    </div>
  </div>

  {{-- ──────────────── Real-Time Traffic (Router RX/​TX + Device Table) ──────────────── --}}
  <div class="card">
    <div class="card-header">
      <h3 class="text-lg font-medium text-gray-800">Real-Time Traffic</h3>
    </div>
    <div class="card-body space-y-4">
      {{-- 1) Chart for Router RX/​TX --}}
      <div class="w-full">
        <canvas id="realTimeChart" height="200"></canvas>
      </div>

      {{-- 2) Table of each device’s current RX/​TX --}}
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left text-gray-600">
          <thead class="bg-gray-100 text-xs text-gray-500 uppercase">
            <tr>
              <th class="px-4 py-2">Hostname (MAC)</th>
              <th class="px-4 py-2">IP Address</th>
              <th class="px-4 py-2">Bytes In</th>
              <th class="px-4 py-2">Bytes Out</th>
              <th class="px-4 py-2">Status</th>
            </tr>
          </thead>
          <tbody id="deviceRealtimeBody" class="bg-white divide-y divide-gray-100">
            {{-- JavaScript will fill this <tbody> on each poll --}}
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

{{-- Chart + AJAX Scripts --}}
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // ──────────────── 1) Build and initialize the Router RX/​TX chart ────────────────
    const ctxRT     = document.getElementById('realTimeChart').getContext('2d');
    const routerChart = new Chart(ctxRT, {
      type: 'line',
      data: {
        labels: [],     // time stamps (e.g. "12:01:05", "12:01:10", …)
        datasets: [
          {
            label: 'Router RX',
            data: [],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            tension: 0.3,
            fill: true,
          },
          {
            label: 'Router TX',
            data: [],
            borderColor: '#ec4899',
            backgroundColor: 'rgba(236,72,153,0.1)',
            tension: 0.3,
            fill: true,
          }
        ]
      },
      options: {
        responsive: true,
        animation: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': ' + formatBytes(context.parsed.y);
              }
            }
          }
        },
        scales: {
          x: {
            title: { display: true, text: 'Time' },
            grid: { display: false },
            ticks: { color: '#4B5563', font: { size: 10 } }
          },
          y: {
            title: { display: true, text: 'Bytes' },
            grid: { color: 'rgba(229,231,235,0.5)' },
            ticks: {
              callback: function(value) { return formatBytes(value); },
              color: '#4B5563',
              font: { size: 10 }
            }
          }
        }
      }
    });

    // ──────────────── 2) Function to poll /realtime-data every 5 seconds ────────────────
    function pollRealtimeData() {
      fetch("{{ route('routers.realtime-data', ['router' => $router->id]) }}")
        .then(res => res.json())
        .then(payload => {
          const now = new Date();
          const ts  = now.getHours().toString().padStart(2,'0') + ':' +
                      now.getMinutes().toString().padStart(2,'0') + ':' +
                      now.getSeconds().toString().padStart(2,'0');

          // ─── Update Router RX/​TX chart ────────────────────────────────────────────────
          routerChart.data.labels.push(ts);
          routerChart.data.datasets[0].data.push(payload.router.rx);
          routerChart.data.datasets[1].data.push(payload.router.tx);

          // Keep only last 12 points (i.e. ~1 minute worth if polling every 5s)
          if (routerChart.data.labels.length > 12) {
            routerChart.data.labels.shift();
            routerChart.data.datasets[0].data.shift();
            routerChart.data.datasets[1].data.shift();
          }
          routerChart.update('none'); // no animation

          // ─── Update device table body ────────────────────────────────────────────────
          const tbody = document.getElementById('deviceRealtimeBody');
          tbody.innerHTML = ''; // clear existing rows
          payload.devices.forEach(device => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td class="px-4 py-2">
                <div class="font-medium text-gray-900">${device.hostname || device.mac}</div>
                <div class="text-xs text-gray-500">${device.mac}</div>
              </td>
              <td class="px-4 py-2">${device.ip}</td>
              <td class="px-4 py-2">${formatBytes(device.bytes_in)}</td>
              <td class="px-4 py-2">${formatBytes(device.bytes_out)}</td>
              <td class="px-4 py-2">
                <span class="inline-flex items-center px-2 py-1 text-xs font-medium 
                  ${device.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'} rounded-full">
                  ${device.active ? 'Active' : 'Inactive'}
                </span>
              </td>
            `;
            tbody.appendChild(tr);
          });
        })
        .catch(() => {
          // ignore errors silently; we’ll try again next tick
        })
        .finally(() => {
          // Schedule next poll in 5 seconds:
          setTimeout(pollRealtimeData, 5000);
        });
    }

    // ──────────────── 3) Kick off the polling loop once DOM is ready ────────────────
    pollRealtimeData();

    // ──────────────── Helper: format raw bytes → “x.x MB” ────────────────
    function formatBytes(bytes) {
      if (!bytes || bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes','KB','MB','GB','TB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }

  });
</script>
@endpush
