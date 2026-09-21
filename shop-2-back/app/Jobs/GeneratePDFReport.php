<?php

namespace App\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GeneratePDFReport implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = [
            'categories' => json_decode(Storage::get('reports/top_categories.json'), true),
            'products' => json_decode(Storage::get('reports/top_products.json'), true),
            'users' => json_decode(Storage::get('reports/top_users.json'), true),
            'periods' => json_decode(Storage::get('reports/top_periods.json'), true),
        ];

        $pdf = Pdf::loadView('reports.top_report', $data);
        Storage::put('reports/top_report.pdf', $pdf->output());
    }
}
