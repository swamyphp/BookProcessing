<?php
// general config
return [
  'upload_max_size' => 200 * 1024 * 1024,
  'allowed_zip_ext' => ['zip'],
  'temp_dir' => __DIR__ . '/../Temp',
  'books_dir' => __DIR__ . '/../Books',
  'uploads_dir' => __DIR__ . '/../Uploads',
  'logs_dir' => __DIR__ . '/../logs'
  ,
  // ClamAV scanning settings
  'clamav_scan' => true,
  // Path to clamscan or clamdscan if available; leave null to auto-detect
  'clamav_bin' => null,
  // After extraction, optionally scan extracted files
  'clamav_scan_after_extract' => false
  ,
  // Redis queue settings for async scanning
  'redis' => [
    'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
    'port' => getenv('REDIS_PORT') ?: 6379,
    'queue_key' => getenv('REDIS_QUEUE_KEY') ?: 'clamav_jobs'
  ]
];
