<?php

namespace App\Jobs;

use App\Mail\MonthlyTopReportMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendTopProfitReportEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $filePath = 'reports/top_report.pdf';

        $fullPath = Storage::path($filePath);

        Mail::to('alexx@ukr.net')->send(new MonthlyTopReportMail($fullPath));
    }
}
