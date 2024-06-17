<?php

use Aws\S3\S3Client;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * configuration for the S3
 */
$s3Config = [
    'version'     => 'latest',
    'region'      => 'fakes3',
    'endpoint'    => getenv('AWS_ENDPOINT'),
    'credentials' => [
        'key'    => '',
        'secret' => ''
    ]
];

$s3Client = new S3Client(
    $s3Config
);

$bucket = getenv('S3_BUCKET');
$prefix = getenv('S3_PREFIX');
if ($prefix) {
    $prefix .= '/';
}

$files = scandir(__DIR__ . '/data');
foreach ($files as $file) {
//    if (mime_content_type(__DIR__ . '/data/' . $file) === 'application/xml') {
        $fileName = __DIR__ . '/data/' . $file;
        $content = file_get_contents($fileName);
        $s3Client->putObject(
            [
                'Bucket' => $bucket,
                'Key'    => $prefix . 'import/' . $file,
                'Body'   => $content
            ]
        );
//    }
}



