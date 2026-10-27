<?php

namespace App\Console\Commands;

use App\Jobs\CalculateAnnualReportJob;
use App\Models\User;
use App\Services\ProfitReportService;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Throwable;

class GenerateAnnualReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:annual {userId} {startDate} {endDate} {categories?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate annual user spend report';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            $userId = $this->argument('userId');
            $startDate = new DateTime($this->argument('startDate'));
            $endDate = new DateTime($this->argument('endDate'));
            $categories = $this->argument('categories')
                ? array_map('intval', explode(',', $this->argument('categories')))
                : null;

            $profitReportService = App::make(ProfitReportService::class);
            $user = User::find($userId);

            $job = new CalculateAnnualReportJob($user, $startDate, $endDate, $categories);
            $job->handle($profitReportService);

            $this->info('Annual report job dispatched successfully.');
        } catch (Throwable $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }
}
