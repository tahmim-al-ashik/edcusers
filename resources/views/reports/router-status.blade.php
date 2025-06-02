{{-- resources/views/reports/router-status.blade.php --}}

@extends('layouts.app')

@section('header')
    <h2 class="text-2xl font-semibold text-gray-800">Router Status Report</h2>
@endsection

@section('content')
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-800">Current Status of All Routers</h3>
        </div>

        <div class="px-6 py-5 space-y-4">
            @forelse($routers as $router)
                <div class="border border-gray-100 rounded-lg hover:bg-gray-50 transition">
                    <div class="px-6 py-4 flex justify-between items-center">
                        {{-- Router Name & IP --}}
                        <div>
                            <p class="text-base font-semibold text-gray-800">
                                {{ $router->name }}
                                <span class="text-sm font-normal text-gray-500 ml-2">
                                    ({{ $router->ip_address }})
                                </span>
                            </p>
                        </div>

                        {{-- Online / Offline Badge --}}
                        <div>
                            @if($router->latestStatus && $router->latestStatus->online)
                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">
                                    <i class="fas fa-check-circle mr-1"></i> Online
                                </span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs font-semibold px-3 py-1 rounded-full">
                                    <i class="fas fa-times-circle mr-1"></i> Offline
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($router->latestStatus)
                        <div class="px-6 pb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-600">
                            {{-- CPU --}}
                            <div class="flex items-center">
                                <i class="fas fa-microchip w-4 h-4 mr-2 text-gray-400"></i>
                                CPU:
                                <span class="font-medium ml-1">
                                    {{ $router->latestStatus->cpu_load }}%
                                </span>
                            </div>

                            {{-- Memory --}}
                            <div class="flex items-center">
                                <i class="fas fa-memory w-4 h-4 mr-2 text-gray-400"></i>
                                Memory:
                                <span class="font-medium ml-1">
                                    {{ number_format($router->latestStatus->memory_usage, 1) }}%
                                </span>
                            </div>

                            {{-- Bandwidth --}}
                            <div class="flex items-center">
                                <i class="fas fa-exchange-alt w-4 h-4 mr-2 text-gray-400"></i>
                                Bandwidth:
                                <span class="text-blue-600 font-medium ml-1">
                                    {{ formatBytes($router->latestStatus->total_bytes_in) }} in
                                </span>,
                                <span class="text-pink-600 font-medium ml-1">
                                    {{ formatBytes($router->latestStatus->total_bytes_out) }} out
                                </span>
                            </div>

                            {{-- Active Connections --}}
                            <div class="flex items-center">
                                <i class="fas fa-network-wired w-4 h-4 mr-2 text-gray-400"></i>
                                Active Conns:
                                <span class="font-medium ml-1">
                                    {{ $router->latestStatus->active_connections }}
                                </span>
                            </div>

                            {{-- Last Checked --}}
                            <div class="flex items-center">
                                <i class="fas fa-clock w-4 h-4 mr-2 text-gray-400"></i>
                                Last checked:
                                <span class="font-medium ml-1">
                                    {{ $router->latestStatus->logged_at->diffForHumans() }}
                                </span>
                            </div>

                            {{-- Connected Devices Count --}}
                            <div class="flex items-center">
                                <i class="fas fa-wifi w-4 h-4 mr-2 text-gray-400"></i>
                                Devices:
                                <span class="text-indigo-700 font-semibold ml-1">
                                    {{ $router->total_devices }}
                                </span>
                                <span class="ml-2 text-green-600">
                                    Active: {{ $router->active_devices }}
                                </span>
                                <span class="ml-2 text-gray-500">
                                    Inactive: {{ $router->inactive_devices }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="px-6 pb-4">
                            <p class="text-sm text-gray-500 italic">Never checked</p>
                        </div>
                    @endif
                </div>
            @empty
                <p class="px-6 py-4 text-sm text-gray-500">No routers found.</p>
            @endforelse
        </div>

        {{-- ─── Pagination Links ─────────────────────────────────────────────── --}}
        @if($routers instanceof \Illuminate\Pagination\AbstractPaginator)
            @if($routers->hasPages())
                <div class="px-6 py-3 bg-gray-100 border-t border-gray-200">
                    {{ $routers->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection

@php
/**
 * Helper to format raw byte values into human-readable strings.
 *
 * Note: Blade will compile this function only once per request.
 */
function formatBytes($bytes) {
    if (empty($bytes) || $bytes === 0) {
        return '0 Bytes';
    }
    $k = 1024;
    $sizes = ['Bytes','KB','MB','GB','TB'];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
@endphp
