<?php

return [
    'disk' => env('REPORT_EXPORT_DISK', env('FILESYSTEM_DISK', 'local')),
    'signed_url_ttl' => (int) env('REPORT_SIGNED_URL_MINUTES', 10),
    'pdf_binary' => env('REPORT_PDF_BINARY'),
    'pdf_timeout' => (int) env('REPORT_PDF_TIMEOUT', 120),
];
