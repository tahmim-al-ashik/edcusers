<?php

// app/Console/Commands/CheckRoutersStatus.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Services\MikroTikService;

class CheckRoutersStatus extends Command
{
    protected $signature = 'routers:check';
    protected $description = 'Check status of all MikroTik routers';

    public function handle(MikroTikService $mikroTikService)
    {
        $routers = Router::where('is_active', true)->get();
        
        foreach ($routers as $router) {
            $this->info("Checking router: {$router->name} ({$router->ip_address})");
            $mikroTikService->checkRouterStatus($router);
        }
        
        $this->info('All routers checked successfully.');
    }
}