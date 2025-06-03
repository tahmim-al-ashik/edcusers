@extends('layouts.app')

@section('title', 'Dashboard')

{{-- Header --}}
@section('header')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
    <a href="{{ route('routers.create') }}"
       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow transition">
        <i class="fas fa-plus mr-2"></i> Add New Router
    </a>
</div>
@endsection

{{-- Main Content --}}
@section('content')
<div class="space-y-8">

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @php
            $cards = [
                ['title' => 'Total Routers',   'value' => $totalRouters,     'color' => 'blue',   'icon' => 'fas fa-server'],
                ['title' => 'Online Routers',  'value' => $onlineRouters,    'color' => 'green',  'icon' => 'fas fa-wifi'],
                ['title' => 'Offline Routers', 'value' => $offlineRouters,   'color' => 'red',    'icon' => 'fas fa-times-circle'],
                ['title' => 'Active Devices',  'value' => "$activeDevices / $totalDevices", 'color' => 'purple', 'icon' => 'fas fa-user-check'],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="bg-white border-l-4 border-{{ $card['color'] }}-600 p-4 shadow rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="text-{{ $card['color'] }}-600">
                        <i class="{{ $card['icon'] }} text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ $card['title'] }}</p>
                        <p class="text-xl font-bold text-gray-800">{{ $card['value'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Real-time RX/TX Chart --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Real-time Bandwidth (RX / TX)</h3>
        <canvas id="realtimeChart" height="120"></canvas>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('realtimeChart').getContext('2d');

    const chartData = {
        labels: [],
        datasets: [
            {
                label: 'RX (In)',
                data: [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'TX (Out)',
                data: [],
                borderColor: '#ec4899',
                backgroundColor: 'rgba(236,72,153,0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    };

    const chartOptions = {
        responsive: true,
        animation: false,
        plugins: {
            legend: {
                position: 'top'
            },
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
                ticks: {
                    color: '#6B7280'
                },
                grid: {
                    display: false
                }
            },
            y: {
                ticks: {
                    callback: function(value) {
                        return formatBytes(value);
                    },
                    color: '#6B7280'
                },
                grid: {
                    color: 'rgba(229,231,235,0.5)'
                }
            }
        }
    };

    const realtimeChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: chartOptions
    });

function formatBytes(bytes) {
    if (typeof bytes !== 'number' || isNaN(bytes)) return '0 Bytes'; // added safety
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
}

    function fetchBandwidth() {
        fetch('/api/bandwidth')
            .then(res => res.json())
            .then(data => {
                if (chartData.labels.length > 30) {
                    chartData.labels.shift();
                    chartData.datasets[0].data.shift();
                    chartData.datasets[1].data.shift();
                }

                chartData.labels.push(data.time);
                chartData.datasets[0].data.push(data.rx);
                chartData.datasets[1].data.push(data.tx);
                realtimeChart.update();
            });
    }

    setInterval(fetchBandwidth, 2000);
});
</script>
@endpush
