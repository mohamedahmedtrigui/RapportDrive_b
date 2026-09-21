<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\ReportAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Report $report)
    {
    }

    public function handle(ReportAnalyzer $analyzer): void
    {
        $analyzer->analyze($this->report);
    }
}
