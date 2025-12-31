<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title ?: ('Report #' . $report->id) }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1, h2 { margin: 0 0 12px; }
        h2 { margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; }
        .meta { font-size: 12px; color: #6b7280; }
    </style>
</head>
<body>
    <h1>{{ $report->title ?: ('Report #' . $report->id) }}</h1>
    <div class="meta">
        Status: {{ $schema['report']['status'] ?? 'draft' }}
        @if($schema['report']['generated_at'])
            | Generated: {{ $schema['report']['generated_at'] }}
        @endif
    </div>

    @if($schema['event'])
        <h2>Event Summary</h2>
        <table>
            <tr>
                <th>Event ID</th>
                <td>{{ $schema['event']['id'] }}</td>
            </tr>
            <tr>
                <th>Name</th>
                <td>{{ $schema['event']['name'] }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ $schema['event']['status'] }}</td>
            </tr>
        </table>
    @endif

    <h2>Evidence Appendix</h2>
    @if(empty($schema['evidence_appendix']))
        <p>No evidence linked yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Evidence ID</th>
                    <th>File</th>
                    <th>Link Type</th>
                    <th>Link ID</th>
                    <th>Report Tag</th>
                </tr>
            </thead>
            <tbody>
                @foreach($schema['evidence_appendix'] as $item)
                    <tr>
                        <td>{{ $item['evidence_id'] }}</td>
                        <td>{{ $item['file_name'] }}</td>
                        <td>{{ $item['link_type'] }}</td>
                        <td>{{ $item['link_id'] }}</td>
                        <td>{{ $item['report_section_tag'] ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
