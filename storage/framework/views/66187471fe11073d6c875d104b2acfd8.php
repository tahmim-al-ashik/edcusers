



<?php $__env->startSection('title', 'Dashboard'); ?>


<?php $__env->startSection('header'); ?>
  <div class="flex justify-between items-center">
    <h2 class="text-2xl font-semibold text-gray-800">Dashboard</h2>
    <a href="<?php echo e(route('routers.create')); ?>"
       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold 
              rounded-md shadow transition">
      <i class="fas fa-plus mr-2"></i> Add New Router
    </a>
  </div>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('content'); ?>
<div class="space-y-6">

  
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
    <?php
      $cards = [
        ['title' => 'Total Routers',   'value' => $totalRouters,     'color' => 'blue',   'icon' => 'fas fa-server'],
        ['title' => 'Online Routers',  'value' => $onlineRouters,    'color' => 'green',  'icon' => 'fas fa-wifi'],
        ['title' => 'Offline Routers', 'value' => $offlineRouters,   'color' => 'red',    'icon' => 'fas fa-times-circle'],
        ['title' => 'Active Devices',  'value' => "$activeDevices / $totalDevices", 'color' => 'purple', 'icon' => 'fas fa-user-check'],
      ];
    ?>

    <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="bg-<?php echo e($card['color']); ?>-100 border-l-4 border-<?php echo e($card['color']); ?>-600 text-<?php echo e($card['color']); ?>-900 p-4 rounded shadow">
        <div class="flex items-center">
          <i class="<?php echo e($card['icon']); ?> w-5 h-5 mr-2"></i>
          <p class="text-sm font-semibold"><?php echo e($card['title']); ?></p>
        </div>
        <p class="mt-2 text-2xl font-bold"><?php echo e($card['value']); ?></p>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  
  <div class="card">
    <div class="card-header">
      <h3 class="text-lg font-medium text-gray-800">Bandwidth Usage (Last 7 Days)</h3>
    </div>
    <div class="card-body">
      <?php if($bandwidthUsage->isEmpty()): ?>
        <p class="text-sm text-gray-500">No bandwidth data available.</p>
      <?php else: ?>
        <canvas id="bandwidthChart" height="120"></canvas>
      <?php endif; ?>
    </div>
  </div>

  
  <?php if($recentlyOffline->isNotEmpty()): ?>
    <div class="card">
      <div class="card-header flex justify-between items-center">
        <h3 class="text-lg font-medium text-red-800">Recently Offline Routers</h3>
        <i class="fas fa-exclamation-triangle text-red-500"></i>
      </div>
      <div class="card-body space-y-4">
        <?php $__currentLoopData = $recentlyOffline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $router): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="border border-gray-100 rounded-lg p-4 hover:bg-red-50 transition">
            <div class="flex justify-between items-center">
              <span class="font-medium text-red-700"><?php echo e($router->name); ?></span>
              <span class="text-sm text-gray-500">
                <?php echo e(optional($router->latestStatus)->logged_at->diffForHumans()); ?>

              </span>
            </div>
            <p class="mt-1 text-sm text-gray-600">
              <?php echo e($router->ip_address); ?> (<?php echo e($router->location ?? 'Unknown'); ?>)
            </p>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

      
      <?php if($recentlyOffline->hasPages()): ?>
        <div class="px-4 py-3 bg-gray-100 border-t border-gray-200">
          <?php echo e($recentlyOffline->links()); ?>

        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>


<?php $__env->startPush('scripts'); ?>
  <?php if($bandwidthUsage->isNotEmpty()): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('bandwidthChart').getContext('2d');
        const dates   = <?php echo json_encode($bandwidthUsage->pluck('date')); ?>;
        const bytesIn = <?php echo json_encode($bandwidthUsage->pluck('total_in')); ?>;
        const bytesOut= <?php echo json_encode($bandwidthUsage->pluck('total_out')); ?>;

        new Chart(ctx, {
          type: 'line',
          data: {
            labels: dates,
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
                  callback: function(value) { return formatBytes(value); },
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
  <?php endif; ?>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\mikrotik-monitor\resources\views/dashboard.blade.php ENDPATH**/ ?>