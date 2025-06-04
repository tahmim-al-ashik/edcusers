<?php

namespace App\Console\Commands;

use App\Classes\RouterOsApi;
use Illuminate\Console\Command;
use App\Models\BroadbandDbSecret;
use App\Models\CorporateClient;
use App\Models\InternetUsers;
use App\Models\User;
use App\Helpers\ResponseWrapper;
use App\Http\Controllers\panel\internet_user\PanelBranchInfoController;
use App\Models\CorporateClientsSettings;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Calculation\Financial\CashFlow\Constant\Periodic\Payments;

class DisableExpiredBroadbandUsers extends Command
{
    protected $signature = 'broadband:disable-expired';
    protected $description = 'Disable users who have not paid after 30 days';

    public function handle()
    {
        Log::info("schedular started!");
        // Call your method here
        $controller = new PanelBranchInfoController();
        $response = $controller->getExpiredUsers();

        $this->info("Disabled users count: " . $response->original['count']);
    }
}
