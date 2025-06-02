{{-- resources/views/reports/bandwidth-usage.blade.php --}}

@extends('layouts.app')

@section('header')
    <h2 class="text-2xl font-semibold text-gray-800">Bandwidth Usage Report</h2>
@endsection

@section('content')
<div class="space-y-6">

    {{-- ──────────────── Filter / Controls Card ──────────────── --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-800">Filter Criteria</h3>
        </div>
        <div class="px-6 py-5">
            <form method="GET" action="{{ route('reports.bandwidth-usage') }}" class="flex flex-wrap gap-6 items-end">
                {{-- Router Selector --}}
                <div class="w-full sm:w-1/3 md:w-1/4">
                    <label for="router_id" class="block text-sm font-medium text-gray-700">Router</label>
                    <select
                        name="router_id"
                        id="router_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        <option value="">All Routers</option>
                        @foreach($routers as $r)
                            <option value="{{ $r->id }}" {{ (int)$routerId === $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Time Range Selector --}}
                <div class="w-full sm:w-1/3 md:w-1/4">
                    <label for="days" class="block text-sm font-medium text-gray-700">Time Range</label>
                    <select
                        name="days"
                        id="days"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                        @foreach([7, 14, 30] as $d)
                            <option value="{{ $d }}" {{ (int)$days === $d ? 'selected' : '' }}>
                                Last {{ $d }} Days
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Generate Report Button --}}
                <div class="w-full sm:w-auto">
                    <button
                        type="submit"
                        class="inline-flex items-center px-5 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <i class="fas fa-sync-alt mr-2"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ──────────────── Chart Card ──────────────── --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-800">
                Bandwidth Usage (Last {{ $days }} {{ \Illuminate\Support\Str::plural('Day', $days) }})
            </h3>
        </div>
        <div class="px-6 py-5">
            @if($usageData->isEmpty())
                <p class="text-sm text-gray-500">No data available for the selected criteria.</p>
            @else
                <div class="w-full">
                    <canvas id="bandwidthChart" height="120"></canvas>
                </div>
            @endif
        </div>
    </div>

</div>

@if($usageData->isNotEmpty())
    @php
        // Group by date and sum bytes_in / bytes_out
        $grouped = $usageData
            ->groupBy('date')
            ->map(function($group) {
                return [
                    'date'      => $group->first()->date,
                    'bytes_in'  => $group->sum('bytes_in'),
                    'bytes_out' => $group->sum('bytes_out'),
                ];
            })
            ->values();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rawData  = @json($grouped);
            const labels   = rawData.map(item => item.date);
            const bytesIn  = rawData.map(item => item.bytes_in);
            const bytesOut = rawData.map(item => item.bytes_out);

            const ctx = document.getElementById('bandwidthChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Bytes In',
                            data: bytesIn,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.1)',
                            tension: 0.4,
                            fill: true,
                        },
                        {
                            label: 'Bytes Out',
                            data: bytesOut,
                            borderColor: '#ec4899',
                            backgroundColor: 'rgba(236,72,153,0.1)',
                            tension: 0.4,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { padding: 16, boxWidth: 12 }
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
                            grid: { display: false },
                            ticks: { color: '#4B5563', font: { size: 12 } }
                        },
                        y: {
                            grid: { color: 'rgba(229,231,235,0.5)' },
                            ticks: {
                                callback: function(value) {
                                    return formatBytes(value);
                                },
                                color: '#4B5563',
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });

            function formatBytes(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes','KB','MB','GB','TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
            }
        });
    </script>
@endif
@endsection
