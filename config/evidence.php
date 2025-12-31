<?php

return [
    'disk' => env('EVIDENCE_DISK', env('FILESYSTEM_DISK', 'local')),
    'signed_url_ttl' => (int) env('EVIDENCE_SIGNED_URL_MINUTES', 10),
];
