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

    public function failed(\Throwable $e): void
    {
        // Belt and braces: analyze() already flips this to 'erreur' on each
        // failed attempt, but this covers a failure outside analyze() itself
        // (e.g. the report row vanishing between attempts).
        $this->report->update(['ia_status' => 'erreur']);
    }
}
