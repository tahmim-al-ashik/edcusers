



<?php $__env->startSection('header'); ?>
    <h2 class="text-2xl font-semibold text-gray-800">
        Edit Router: <?php echo e($router->name); ?>

    </h2>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl mx-auto space-y-6">

    
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-800">Router Details</h3>
        </div>
        <div class="card-body">
            <form action="<?php echo e(route('routers.update', $router)); ?>" method="POST" class="space-y-6">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Router Name <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            required
                            value="<?php echo e(old('name', $router->name)); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    
                    <div>
                        <label for="ip_address" class="block text-sm font-medium text-gray-700">IP Address <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="ip_address"
                            id="ip_address"
                            required
                            value="<?php echo e(old('ip_address', $router->ip_address)); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        <?php $__errorArgs = ['ip_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    
                    <div>
                        <label for="port" class="block text-sm font-medium text-gray-700">API Port</label>
                        <input
                            type="number"
                            name="port"
                            id="port"
                            value="<?php echo e(old('port', $router->port)); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        <?php $__errorArgs = ['port'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Admin Username <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            required
                            value="<?php echo e(old('username', $router->username)); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        <?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            value="<?php echo e(old('password', $router->password)); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                        <input
                            type="text"
                            name="location"
                            id="location"
                            value="<?php echo e(old('location', $router->location)); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                        <?php $__errorArgs = ['location'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm 
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        ><?php echo e(old('description', $router->description)); ?></textarea>
                        <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                
                <div class="flex items-center">
                    <input
                        id="is_active"
                        name="is_active"
                        type="checkbox"
                        <?php echo e(old('is_active', $router->is_active) ? 'checked' : ''); ?>

                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    >
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">Is Active?</label>
                </div>
                <?php $__errorArgs = ['is_active'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                
                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                    <a 
                        href="<?php echo e(route('routers.index')); ?>"
                        class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent 
                               rounded-md font-medium text-white hover:bg-gray-600 focus:outline-none 
                               focus:ring-2 focus:ring-gray-500"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent 
                               rounded-md font-medium text-white hover:bg-indigo-700 focus:outline-none 
                               focus:ring-2 focus:ring-indigo-500"
                    >
                        Update Router
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\mikrotik-monitor\resources\views/routers/edit.blade.php ENDPATH**/ ?>