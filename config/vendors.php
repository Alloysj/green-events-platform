<?php

return [
    // Days prior to expiry when a vendor should be flagged for reverification
    'reverification_days' => env('VENDORS_REVERIFICATION_DAYS', 30),
];