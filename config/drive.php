<?php

return [
    // Keep PHP and the web server's request limits above this per-file limit.
    'max_upload_size_mb' => (int) env('DRIVE_MAX_UPLOAD_SIZE_MB', 1024),
];
