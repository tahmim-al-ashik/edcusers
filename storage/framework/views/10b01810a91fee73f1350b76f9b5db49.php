

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $__env->yieldContent('title', 'MikroTik Monitor'); ?></title>
  <link rel="stylesheet" href="<?php echo e(mix('css/app.css')); ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 text-gray-900">

  
  <nav class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center">
      
      <a href="<?php echo e(route('dashboard')); ?>" class="text-xl font-bold text-indigo-700">
        MikroTik Monitor
      </a>

      <div class="flex-1"></div>

      
      <a href="<?php echo e(route('dashboard')); ?>"
         class="<?php echo e(request()->routeIs('dashboard')
                     ? 'text-indigo-700 font-semibold'
                     : 'text-gray-700 hover:text-indigo-600'); ?> text-sm">
        Dashboard
      </a>

      <span class="mx-4 text-gray-300">|</span>

      
      <a href="<?php echo e(route('routers.index')); ?>"
         class="<?php echo e(request()->routeIs('routers.*')
                     ? 'text-indigo-700 font-semibold'
                     : 'text-gray-700 hover:text-indigo-600'); ?> text-sm">
        Routers
      </a>
    </div>
  </nav>

  
  <header class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <?php echo $__env->yieldContent('header'); ?>
  </header>

  
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php echo $__env->yieldContent('content'); ?>
  </main>

  <script src="<?php echo e(mix('js/app.js')); ?>"></script>
  <?php echo $__env->yieldPushContent('scripts'); ?>


  
  <?php
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
  ?>

</body>
</html>
<?php /**PATH C:\xampp\htdocs\mikrotik-monitor\resources\views/layouts/app.blade.php ENDPATH**/ ?>