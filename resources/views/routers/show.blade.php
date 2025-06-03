{{-- resources/views/routers/show.blade.php --}}

@extends('layouts.app')

@section('title', "Router Details: {$router->name}")

{{-- Page Header --}}
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

{{-- Main Content --}}
@section('content')
<div class="space-y-6">

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- Status Card --}}
  <div class="bg-white rounded-lg shadow-sm">
    <div class="flex justify-between items-center px-4 py-3 border-b">
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
    <div class="px-4 py-4">
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
            <p class="text-sm text-blue-600">In: {{ formatBytes($router->latestStatus->total_bytes_in) }}</p>
            <p class="text-sm text-pink-600">Out: {{ formatBytes($router->latestStatus->total_bytes_out) }}</p>
          </div>
        </div>
      @else
        <p class="text-sm text-gray-500 italic">Router is currently offline or has never been checked.</p>
      @endif
    </div>
  </div>

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- Router Info Card --}}
  <div class="bg-white rounded-lg shadow-sm">
    <div class="px-4 py-3 border-b">
      <h3 class="text-lg font-medium text-gray-800">Router Info</h3>
    </div>
    <div class="px-4 py-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-gray-700">
        <div><strong>IP:</strong> {{ $router->ip_address }}:{{ $router->port }}</div>
        <div><strong>Location:</strong> {{ $router->location ?? 'Not specified' }}</div>
        <div><strong>Username:</strong> {{ $router->username }}</div>
        <div><strong>Status:</strong> {{ $router->is_active ? 'Active' : 'Disabled' }}</div>
        <div><strong>Total Devices:</strong> <span class="font-semibold">{{ $paginatedDevices->total() }}</span></div>
      </div>
      @if($router->description)
        <p class="mt-4 text-sm text-gray-600">
          <strong>Description:</strong> {{ $router->description }}
        </p>
      @endif
    </div>
  </div>

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- 7-Day Bandwidth Chart --}}
  <div class="bg-white rounded-lg shadow-sm">
    <div class="px-4 py-3 border-b">
      <h3 class="text-lg font-medium text-gray-800">Bandwidth (Last 7 Days)</h3>
    </div>
    <div class="px-4 py-4">
      <canvas id="routerBwChart" height="120"></canvas>
    </div>
  </div>

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- Dynamic + Running Interfaces (DR) / Running Interfaces (R) --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- DR Interfaces --}}
    <div class="bg-white rounded-lg shadow-sm">
      <div class="px-4 py-3 border-b">
        <h3 class="text-lg font-medium text-gray-800">Dynamic + Running Interfaces (DR)</h3>
      </div>
      <div class="px-4 py-4">
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
                  <span class="text-blue-600 font-semibold">↓ {{ $rxBytes ? formatBytes($rxBytes) : '0 Bytes' }}</span><br>
                  <span class="text-pink-600 font-semibold">↑ {{ $txBytes ? formatBytes($txBytes) : '0 Bytes' }}</span>
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

    {{-- R Interfaces --}}
    <div class="bg-white rounded-lg shadow-sm">
      <div class="px-4 py-3 border-b">
        <h3 class="text-lg font-medium text-gray-800">Running Interfaces (R)</h3>
      </div>
      <div class="px-4 py-4">
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
                  <span class="text-blue-600 font-semibold">↓ {{ $rxBytes ? formatBytes($rxBytes) : '0 Bytes' }}</span><br>
                  <span class="text-pink-600 font-semibold">↑ {{ $txBytes ? formatBytes($txBytes) : '0 Bytes' }}</span>
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
  </div>

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- Connected Devices (Paginated) --}}
  <div class="bg-white rounded-lg shadow-sm">
    <div class="flex justify-between items-center px-4 py-3 border-b">
      <h3 class="text-lg font-medium text-gray-800">
        Connected Devices ({{ $paginatedDevices->total() }})
      </h3>
      <div class="space-x-2 text-sm">
        <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 font-medium text-xs rounded">
          <i class="fas fa-wifi mr-1"></i> Active: {{ $paginatedDevices->where('active', true)->count() }}
        </span>
        <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-800 font-medium text-xs rounded">
          <i class="fas fa-user-slash mr-1"></i> Inactive: {{ $paginatedDevices->where('active', false)->count() }}
        </span>
      </div>
    </div>
    <div class="px-4 py-4 space-y-4">
      @if($paginatedDevices->isEmpty())
        <p class="text-sm text-gray-500">No devices found or router is offline.</p>
      @else
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-left text-gray-600 border">
            <thead class="bg-gray-100 text-xs text-gray-500 uppercase">
              <tr>
                <th class="px-4 py-2">Interface</th>
                <th class="px-4 py-2">Hostname (MAC)</th>
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
                    <span class="text-blue-600">↓ {{ $inBytes ? formatBytes($inBytes) : '0 Bytes' }}</span><br>
                    <span class="text-pink-600">↑ {{ $outBytes ? formatBytes($outBytes) : '0 Bytes' }}</span>
                  </td>
                  <td class="px-4 py-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
                      {{ $device->active 
                          ? 'bg-green-100 text-green-700' 
                          : 'bg-gray-100 text-gray-700' }}">
                      {{ $device->active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="px-4 py-2">
                    <form action="{{ route('routers.devices.destroy', [$router, $device]) }}" method="POST"
                          onsubmit="return confirm('Remove this device?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit"
                              class="inline-flex items-center px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200">
                        <i class="fas fa-trash-alt mr-1"></i> Remove
                      </button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Pagination Links --}}
        @if($paginatedDevices->hasPages())
          <div class="px-4 py-3 bg-gray-100 border-t border-gray-200">
            {{ $paginatedDevices->links() }}
          </div>
        @endif
      @endif
    </div>
  </div>

  {{-- ──────────────────────────────────────────────────────────────────── --}}
  {{-- Real-Time Live View --}}
  <div class="bg-white rounded-lg shadow-sm">
    <div class="px-4 py-3 border-b">
      <h3 class="text-lg font-medium text-gray-800">Real-Time Live View</h3>
    </div>
    <div class="px-4 py-4 space-y-6">

      {{-- Combined Interfaces Live Graph --}}
      <div class="p-4 bg-gray-50 rounded shadow-sm">
        <h4 class="text-md font-semibold text-gray-800 mb-2">Combined Interfaces Live Graph</h4>
        <canvas id="combinedChart" height="150"></canvas>
      </div>

      {{-- Per-Device RX/TX Graphs + Status --}}
      <div id="deviceCharts" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- JS will inject one card per device here --}}
      </div>
    </div>
  </div>
</div>
@endsection

{{-- Live AJAX & Chart.js Scripts --}}
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // ─── Helper: convert raw bytes to human-readable ─────────────────────────
    function formatBytes(bytes) {
      if (!bytes || bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes','KB','MB','GB','TB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }

    // ─── 1) Initialize combined RX/TX chart ─────────────────────────────────
    const ctxCombined = document.getElementById('combinedChart').getContext('2d');
    const combinedChart = new Chart(ctxCombined, {
      type: 'line',
      data: {
        labels: [],
        datasets: [
          {
            label: 'Total RX',
            data: [],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.1)',
            tension: 0.3,
            fill: true
          },
          {
            label: 'Total TX',
            data: [],
            borderColor: '#ec4899',
            backgroundColor: 'rgba(236,72,153,0.1)',
            tension: 0.3,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        animation: false,
        plugins: {
          legend: { position: 'top', labels: { font: { size: 12 } } },
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

    // ─── Track per-device Chart.js instances ───────────────────────────────
    const deviceCharts = {};

    // ─── Polling loop ─────────────────────────────────────────────────────
    function pollLive() {
      fetch("{{ route('routers.realtime-data', ['router' => $router->id]) }}")
        .then(res => res.json())
        .then(payload => {
          const now = new Date();
          const ts  = now.getHours().toString().padStart(2, '0') + ':' +
                      now.getMinutes().toString().padStart(2, '0') + ':' +
                      now.getSeconds().toString().padStart(2, '0');

          // Update combined chart
          combinedChart.data.labels.push(ts);
          combinedChart.data.datasets[0].data.push(payload.router.rx);
          combinedChart.data.datasets[1].data.push(payload.router.tx);
          if (combinedChart.data.labels.length > 12) {
            combinedChart.data.labels.shift();
            combinedChart.data.datasets[0].data.shift();
            combinedChart.data.datasets[1].data.shift();
          }
          combinedChart.update('none');

          // Create/update per-device charts
          const container = document.getElementById('deviceCharts');
          payload.devices.forEach(device => {
            const devId = device.id;
            const key   = `dev-${devId}`;

            if (!deviceCharts[key]) {
              // Build card + canvas + status badge
              const card = document.createElement('div');
              card.className = 'p-4 bg-white rounded shadow-sm flex flex-col';
              card.id = `card-${devId}`;

              // Header row: name + badge
              const header = document.createElement('div');
              header.className = 'flex justify-between items-center mb-2';

              const title = document.createElement('h4');
              title.className = 'text-md font-semibold text-gray-800';
              title.innerText = device.hostname || device.mac;
              header.appendChild(title);

              const statusBadge = document.createElement('span');
              statusBadge.id = `status-${devId}`;
              statusBadge.className = device.active
                ? 'inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full'
                : 'inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full';
              statusBadge.innerText = device.active ? 'Active' : 'Inactive';
              header.appendChild(statusBadge);

              card.appendChild(header);

              // Canvas element
              const canvas = document.createElement('canvas');
              canvas.id = `chart-${devId}`;
              canvas.height = 120;
              card.appendChild(canvas);

              container.appendChild(card);

              // Initialize Chart.js for this device
              const ctxDev = canvas.getContext('2d');
              deviceCharts[key] = new Chart(ctxDev, {
                type: 'line',
                data: {
                  labels: [],
                  datasets: [
                    {
                      label: 'RX',
                      data: [],
                      borderColor: '#3b82f6',
                      backgroundColor: 'rgba(59,130,246,0.1)',
                      tension: 0.3,
                      fill: true
                    },
                    {
                      label: 'TX',
                      data: [],
                      borderColor: '#ec4899',
                      backgroundColor: 'rgba(236,72,153,0.1)',
                      tension: 0.3,
                      fill: true
                    }
                  ]
                },
                options: {
                  responsive: true,
                  animation: false,
                  plugins: {
                    legend: { position: 'top', labels: { font: { size: 10 } } },
                    tooltip: {
                      callbacks: {
                        label: function(ctx) {
                          return ctx.dataset.label + ': ' + formatBytes(ctx.parsed.y);
                        }
                      }
                    }
                  },
                  scales: {
                    x: {
                      display: false,
                      grid: { display: false }
                    },
                    y: {
                      grid: { color: 'rgba(229,231,235,0.5)' },
                      ticks: {
                        callback: function(val) { return formatBytes(val); },
                        font: { size: 10 }
                      }
                    }
                  }
                }
              });
            }

            // Update existing device chart data & badge
            const devChart = deviceCharts[key];
            devChart.data.labels.push(ts);
            devChart.data.datasets[0].data.push(device.bytes_in);
            devChart.data.datasets[1].data.push(device.bytes_out);
            if (devChart.data.labels.length > 12) {
              devChart.data.labels.shift();
              devChart.data.datasets[0].data.shift();
              devChart.data.datasets[1].data.shift();
            }
            devChart.update('none');

            // Update status badge
            const badgeEl = document.getElementById(`status-${devId}`);
            badgeEl.innerText = device.active ? 'Active' : 'Inactive';
            badgeEl.className = device.active
              ? 'inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full'
              : 'inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full';
          });
        })
        .catch(() => {
          // ignore errors; retry next tick
        })
        .finally(() => {
          setTimeout(pollLive, 5000);
        });
    }

    // Start polling
    pollLive();
  });
</script>
@endpush
