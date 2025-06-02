



<?php $__env->startSection('title', 'All Routers'); ?>


<?php $__env->startSection('header'); ?>
  <div class="flex justify-between items-center">
    <h2 class="text-2xl font-semibold text-gray-800">Routers</h2>
    <a href="<?php echo e(route('routers.create')); ?>"
       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold 
              rounded-md shadow transition">
      <i class="fas fa-plus mr-2"></i> Add New Router
    </a>
  </div>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('content'); ?>
<div class="space-y-6">

  
  <div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
      <h3 class="text-lg font-medium text-gray-800">All Routers</h3>
    </div>
    <div class="p-4 space-y-4">
      <?php $__empty_1 = true; $__currentLoopData = $routers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $router): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="border border-gray-100 rounded-lg hover:bg-gray-50 transition">
          <div class="px-6 py-4 flex justify-between items-center">
            
            <div class="text-indigo-700 font-semibold text-base">
              <a href="<?php echo e(route('routers.show', $router)); ?>">
                <i class="fas fa-network-wired mr-1"></i> <?php echo e($router->name); ?>

              </a>
            </div>

            
            <div>
              <?php if($router->latestStatus && $router->latestStatus->online): ?>
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded-full">
                  <i class="fas fa-wifi mr-1"></i> Online
                </span>
              <?php else: ?>
                <span class="inline-flex items-center px-3 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded-full">
                  <i class="fas fa-times-circle mr-1"></i> Offline
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="px-6 pb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-600">
            
            <div class="flex items-center">
              <i class="fas fa-globe w-5 h-5 mr-2 text-gray-400"></i>
              <?php echo e($router->ip_address); ?> : <?php echo e($router->port); ?>

            </div>

            
            <div class="flex items-center">
              <i class="fas fa-map-marker-alt w-5 h-5 mr-2 text-gray-400"></i>
              <?php echo e($router->location ?? 'Unknown'); ?>

            </div>

            
            <div class="flex items-center">
              <i class="fas fa-clock w-5 h-5 mr-2 text-gray-400"></i>
              <?php if($router->latestStatus): ?>
                Last checked: <span class="font-medium ml-1"><?php echo e($router->latestStatus->logged_at->diffForHumans()); ?></span>
              <?php else: ?>
                <span class="font-medium">Never checked</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="px-6 pt-2 flex justify-end space-x-2">
            <a href="<?php echo e(route('routers.show', $router)); ?>"
               class="inline-flex items-center px-3 py-1 border rounded-md text-sm bg-white hover:bg-gray-100 text-gray-700">
              <i class="fas fa-eye mr-1"></i> View
            </a>
            <a href="<?php echo e(route('routers.edit', $router)); ?>"
               class="inline-flex items-center px-3 py-1 border rounded-md text-sm bg-white hover:bg-gray-100 text-gray-700">
              <i class="fas fa-edit mr-1"></i> Edit
            </a>
            <form action="<?php echo e(route('routers.destroy', $router)); ?>" method="POST" onsubmit="return confirm('Are you sure?');">
              <?php echo csrf_field(); ?>
              <?php echo method_field('DELETE'); ?>
              <button type="submit"
                      class="inline-flex items-center px-3 py-1 border rounded-md text-sm bg-white hover:bg-gray-100 text-gray-700">
                <i class="fas fa-trash-alt mr-1"></i> Delete
              </button>
            </form>
            <form action="<?php echo e(route('routers.check-now', $router)); ?>" method="POST">
              <?php echo csrf_field(); ?>
              <button type="submit"
                      class="inline-flex items-center px-3 py-1 border rounded-md text-sm bg-white hover:bg-gray-100 text-gray-700">
                <i class="fas fa-sync-alt mr-1"></i> Check Now
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="px-6 py-4 text-sm text-gray-500">No routers found.</p>
      <?php endif; ?>
    </div>

    
    <?php if($routers instanceof \Illuminate\Pagination\AbstractPaginator && $routers->hasPages()): ?>
      <div class="px-6 py-3 bg-gray-100 border-t border-gray-200">
        <?php echo e($routers->links()); ?>

      </div>
    <?php endif; ?>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\mikrotik-monitor\resources\views/routers/index.blade.php ENDPATH**/ ?>