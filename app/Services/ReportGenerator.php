<?php

namespace App\Services;

use App\Models\Domains\Evidence\EvidenceLink;
use App\Models\Domains\Events\Event;
use App\Models\Domains\Reporting\Report;
use App\Models\Domains\Reporting\ReportExport;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Symfony\Component\Process\Process;

class ReportGenerator
{
    public function buildSchema(Report $report): array
    {
        $event = $report->event;

        return [
            'report' => [
                'id' => $report->id,
                'title' => $report->title,
                'status' => $report->status,
                'generated_at' => optional($report->generated_at)->toIso8601String(),
                'published_at' => optional($report->published_at)->toIso8601String(),
            ],
            'event' => $event ? [
                'id' => $event->id,
                'name' => $event->name,
                'status' => $event->status,
            ] : null,
            'evidence_appendix' => $this->buildEvidenceAppendix($report),
        ];
    }

    public function renderHtml(Report $report, array $schema): string
    {
        return View::make('reports.report', [
            'report' => $report,
            'schema' => $schema,
        ])->render();
    }

    public function generatePdf(Report $report, ReportExport $export): void
    {
        $binary = config('reporting.pdf_binary');

        if (! $binary) {
            $this->markFailed($export, 'No PDF binary configured.');
            return;
        }

        $schema = $this->buildSchema($report);
        $html = $this->renderHtml($report, $schema);

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $htmlPath = $tmpDir.'/report-'.$report->id.'-'.$export->id.'.html';
        $pdfPath = $tmpDir.'/report-'.$report->id.'-'.$export->id.'.pdf';

        file_put_contents($htmlPath, $html);

        $process = new Process([$binary, $htmlPath, $pdfPath]);
        $process->setTimeout(config('reporting.pdf_timeout', 120));
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($pdfPath)) {
            $this->markFailed($export, $process->getErrorOutput() ?: 'PDF generation failed.');
            @unlink($htmlPath);
            @unlink($pdfPath);
            return;
        }

        $disk = config('reporting.disk', 'local');
        $path = 'reports/'.$report->id.'/report-'.$export->id.'.pdf';

        Storage::disk($disk)->put($path, file_get_contents($pdfPath));

        $export->status = ReportExport::STATUS_COMPLETE;
        $export->storage_disk = $disk;
        $export->storage_path = $path;
        $export->mime_type = 'application/pdf';
        $export->save();

        $this->markReportGenerated($report);
        $this->audit($report, $export);

        @unlink($htmlPath);
        @unlink($pdfPath);
    }

    public function generateJson(Report $report, ReportExport $export): void
    {
        $schema = $this->buildSchema($report);
        $payload = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $disk = config('reporting.disk', 'local');
        $path = 'reports/'.$report->id.'/report-'.$export->id.'.json';

        Storage::disk($disk)->put($path, $payload ?: '{}');

        $export->status = ReportExport::STATUS_COMPLETE;
        $export->storage_disk = $disk;
        $export->storage_path = $path;
        $export->mime_type = 'application/json';
        $export->save();

        $this->markReportGenerated($report);
        $this->audit($report, $export);
    }

    private function buildEvidenceAppendix(Report $report): array
    {
        $links = EvidenceLink::query()
            ->with('evidence')
            ->where(function ($query) use ($report) {
                $query->where(function ($query) use ($report) {
                    $query->where('linkable_type', Report::class)
                        ->where('linkable_id', $report->id);
                });

                if ($report->event_id) {
                    $query->orWhere(function ($query) use ($report) {
                        $query->where('linkable_type', Event::class)
                            ->where('linkable_id', $report->event_id);
                    });
                }
            })
            ->orderBy('id')
            ->get();

        return $links->map(function (EvidenceLink $link): array {
            $evidence = $link->evidence;

            return [
                'evidence_id' => $link->evidence_artifact_id,
                'file_name' => $evidence?->file_name,
                'link_type' => class_basename($link->linkable_type),
                'link_id' => $link->linkable_id,
                'report_section_tag' => $link->report_section_tag,
            ];
        })->all();
    }

    private function markReportGenerated(Report $report): void
    {
        if ($report->status === Report::STATUS_DRAFT) {
            $report->status = Report::STATUS_GENERATED;
        }

        if (! $report->generated_at) {
            $report->generated_at = now();
        }

        $report->save();
    }

    private function audit(Report $report, ReportExport $export): void
    {
        app(AuditLogger::class)->log('report.exported', [
            'report_id' => $report->id,
            'export_type' => $export->type,
            'export_id' => $export->id,
        ], auth()->user());
    }

    private function markFailed(ReportExport $export, string $message): void
    {
        $export->status = ReportExport::STATUS_FAILED;
        $export->error_message = $message;
        $export->save();
    }
}
