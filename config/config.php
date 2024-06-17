<?php

$s3Config = [
    'version' => getenv('AWS_VERSION'),
    'region'  => getenv('AWS_REGION'),
];

if (getenv('AWS_ENDPOINT')) {
    $s3Config['endpoint'] = getenv('AWS_ENDPOINT');
    $s3Config['credentials'] = [
        'key'    => getenv('AWS_KEY'),
        'secret' => getenv('AWS_SECRET'),
    ];
}

return [
    's3'              => $s3Config,
    's3bucket'        => getenv('S3_BUCKET'),
    's3prefix'        => getenv('S3_PREFIX'),
    'restEndpointUrl' => getenv('REST_ENDPOINT_URL'),
    'sentry'          => [
        'dsn'         => 'https://' . getenv('SENTRY_PUBLIC_KEY') . '@sentry.io/' . getenv('SENTRY_PROJECT_ID'),
        'release'     => getenv('COMMIT_HASH'),
        'environment' => getenv('ENVIRONMENT'),
    ],
    'jira'            => [
        'enable'             => getenv('JIRA_ENABLE'),
        'enableCreateTicket' => getenv('JIRA_ENABLE_CREATE_TICKET'),
        'host'               => getenv('JIRA_HOST'),
        'username'           => getenv('JIRA_USERNAME'),
        'password'           => getenv('JIRA_PASSWORD'),
        'project'            => getenv('JIRA_PROJECT'),
        'issueType'          => getenv('JIRA_ISSUETYPE'),
    ]
];