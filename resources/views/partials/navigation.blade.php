<nav class="fixed top-0 left-0 right-0 z-30 bg-white/90 backdrop-blur-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            {{-- Branding --}}
            <div class="flex-shrink-0 flex items-center">
                <i class="fas fa-broadcast-tower text-indigo-700 text-2xl mr-2"></i>
                <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-indigo-700">
                    MikroTik Monitor
                </a>
            </div>

            {{-- Desktop Navigation Links --}}
            <div class="hidden md:flex space-x-6">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition
                          {{ request()->routeIs('dashboard') 
                              ? 'bg-indigo-600 text-white' 
                              : 'text-gray-700 hover:bg-indigo-500 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                </a>
                <a href="{{ route('routers.index') }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition
                          {{ request()->routeIs('routers.*') 
                              ? 'bg-indigo-600 text-white' 
                              : 'text-gray-700 hover:bg-indigo-500 hover:text-white' }}">
                    <i class="fas fa-network-wired mr-1"></i> Routers
                </a>
                <a href="{{ route('reports.router-status') }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition
                          {{ request()->routeIs('reports.router-status') 
                              ? 'bg-indigo-600 text-white' 
                              : 'text-gray-700 hover:bg-indigo-500 hover:text-white' }}">
                    <i class="fas fa-list-check mr-1"></i> Status Report
                </a>
                <a href="{{ route('reports.bandwidth-usage') }}"
                   class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition
                          {{ request()->routeIs('reports.bandwidth-usage') 
                              ? 'bg-indigo-600 text-white' 
                              : 'text-gray-700 hover:bg-indigo-500 hover:text-white' }}">
                    <i class="fas fa-chart-line mr-1"></i> Bandwidth
                </a>
            </div>

            {{-- Mobile Menu Button (if needed) --}}
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-button" class="text-gray-700 hover:text-indigo-600 focus:outline-none">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Optionally, you can add a mobile‐menu dropdown below (collapsed by default) --}}
    {{-- 
    <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-200">
        <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-gray-700 hover:bg-indigo-500 hover:text-white">
            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
        </a>
        <a href="{{ route('routers.index') }}" class="block px-4 py-2 text-gray-700 hover:bg-indigo-500 hover:text-white">
            <i class="fas fa-network-wired mr-1"></i> Routers
        </a>
        <a href="{{ route('reports.router-status') }}" class="block px-4 py-2 text-gray-700 hover:bg-indigo-500 hover:text-white">
            <i class="fas fa-list-check mr-1"></i> Status Report
        </a>
        <a href="{{ route('reports.bandwidth-usage') }}" class="block px-4 py-2 text-gray-700 hover:bg-indigo-500 hover:text-white">
            <i class="fas fa-chart-line mr-1"></i> Bandwidth
        </a>
    </div>
    --}}

</nav>

{{-- Spacer so content doesn’t hide behind the fixed nav --}}
<div class="h-16"></div>

{{-- (Optional) Mobile Menu Toggle Script --}}
<script>
    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        if (!menu) return;
        menu.classList.toggle('hidden');
    });
</script>
