<?php

namespace App\Jobs;

use App\Jobs\Middleware\RateLimitJob;
use App\Mail\AnnualReportMail;
use App\Models\User;
use App\Services\ProfitReportService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class CalculateAnnualReportJob implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly User $receiver,
        private readonly DateTime $startDate,
        private readonly DateTime $endDate,
        private readonly ?array $categories = null
    ) {
        //
    }

    public function middleware(): array
    {
        return [new RateLimitJob];
    }

    /**
     * Execute the job.
     */
    public function handle(ProfitReportService $service): void
    {
        Mail::to($this->receiver->email)->send(
            new AnnualReportMail(
                $service->getAnnualUsersReport($this->receiver, $this->startDate, $this->endDate, $this->categories)
            )
        );
    }
}
