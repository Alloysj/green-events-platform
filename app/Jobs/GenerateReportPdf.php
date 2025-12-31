<?php

namespace App\Jobs;

use App\Models\Domains\Reporting\Report;
use App\Models\Domains\Reporting\ReportExport;
use App\Services\ReportGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $reportId,
        public ?int $userId = null,
    ) {
    }

    public function handle(ReportGenerator $generator): void
    {
        $report = Report::findOrFail($this->reportId);

        $export = ReportExport::create([
            'report_id' => $report->id,
            'type' => ReportExport::TYPE_PDF,
            'status' => ReportExport::STATUS_PENDING,
            'created_by' => $this->userId,
        ]);

        $generator->generatePdf($report, $export);
    }
}
