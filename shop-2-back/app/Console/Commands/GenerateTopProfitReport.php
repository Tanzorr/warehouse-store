<?php

namespace App\Console\Commands;

use App\Jobs\GeneratePDFReport;
use App\Jobs\GenerateTopCategoriesReport;
use App\Jobs\GenerateTopProductsReport;
use App\Jobs\GenerateTopTimePeriodsReport;
use App\Jobs\GenerateTopUsersReport;
use App\Jobs\SendTopProfitReportEmail;
use Illuminate\Console\Command;

class GenerateTopProfitReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-top-profit-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate top profit sales report and send via email';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting report generation...');

        dispatch_sync(new GenerateTopCategoriesReport);
        dispatch_sync(new GenerateTopProductsReport);
        dispatch_sync(new GenerateTopUsersReport);
        dispatch_sync(new GenerateTopTimePeriodsReport);
        dispatch_sync(new GeneratePDFReport);
        dispatch_sync(new SendTopProfitReportEmail);

        $this->info('Top profit report generated!');
    }
}
