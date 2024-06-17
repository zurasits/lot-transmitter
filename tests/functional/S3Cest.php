<?php

namespace LotTransmitterTest\functional;

use Aws\S3\S3Client;
use InvalidArgumentException;
use LotTransmitter\LotTransmitter;

use Monolog\Handler\RavenHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use privatrepo\Logger\Handler\CliOutputHandler;
use privatrepo\Logger\Logger\SentryHandlerFactory;



class S3Cest
{
    /**
     * bucket for the codeception tests
     */
    private const S3_BUCKET_NAME = 'lot_data';

    private const S3_ROOT = __DIR__ . '/../../dev/s3/' . self::S3_BUCKET_NAME;

    /**
     * @var S3Client
     */
    private $s3Client;

    /**
     * @var LotTransmitter
     */
    private $lotTransmitter;

    /**
     * init the database (fixtures)
     */
    public function _before(): void
    {

        $this->deleteFiles(self::S3_ROOT);
        /**
         * genearl config for everything
         */
        $config = [
            's3' => [
                'version' => 'latest',
                'region' => getenv('AWS_REGION'),
                'endpoint' => getenv('AWS_ENDPOINT'),
                'credentials' => [
                    'key' => getenv('AWS_KEY'),
                    'secret' => getenv('AWS_SECRET')
                ],
            ],
            's3bucket' => getenv('S3_BUCKET'),
            'restEndpointUrl' => getenv('REST_ENDPOINT_URL'),
            'jira' => [
                'enableCreateTicket' => getenv('JIRA_ENABLE_CREATE_TICKET'),
                'enable' => getenv('JIRA_ENABLE'),
                'host' => getenv('JIRA_HOST'),
                'username' => getenv('JIRA_USERNAME'),
                'password' => getenv('JIRA_PASSWORD'),
                'project' => getenv('JIRA_PROJECT'),
                'issueType' => getenv('JIRA_ISSUETYPE'),
            ]
        ];

        $loggerHandler = [
            'default' => [
                'class'      => StreamHandler::class,
                'args'       => [
                    'path'  => getenv('LOGGER_PATH') ?: 'php://stdout',
                    'level' => Logger::DEBUG,
                ],
                'formatter'  => [
                    'class'          => \Monolog\Formatter\JsonFormatter::class,
                    'args'           => [
                        'ignoreEmptyContextAndExtra' => false,
                    ],
                    'showStackTrace' => true,
                ],
                'processors' => [
                    WebProcessor::class,
                    [
                        'class' => IntrospectionProcessor::class,
                        'args'  => [
                            'level' => Logger::DEBUG,
                        ],
                    ],
                ],
            ],
        ];

        if (getenv('SENTRY_PROJECT_ID')) {
            $loggerHandler['sentry'] = [
                'class'   => RavenHandler::class,
                'dsn'     => 'https://' . getenv('SENTRY_PUBLIC_KEY') . '@sentry.io/' . getenv('SENTRY_PROJECT_ID'),
                'options' => [
                    'release' => getenv('COMMIT_HASH'),
                ],
                'level'   => Logger::ERROR,
            ];
        }

        $loggerHandler['cli-output'] = [
            'class'     => CliOutputHandler::class,
            'formatter' => [
                'class'          => \Monolog\Formatter\JsonFormatter::class,
                'showStackTrace' => true,
                'args'           => [
                ],
            ],
        ];


        $config['logger'] = [
            'dependencies'     => [
                'factories' => [
                    \ExpressiveLogger\Logger::class => \ExpressiveLogger\LoggerFactory::class,
                    RavenHandler::class             => SentryHandlerFactory::class,
                ],
            ],
            'expressiveLogger' => [
                'channelName'                => 'expressiveLogger',
                'handlers'                   => [],
                'exceptionFormatterCallback' => null,
                'messageFormatter'           => \ExpressiveLogger\MessageFormatter\DefaultFormatter::class,
                'registerErrorHandler'       => true,
                'ignoredExceptionClasses'    => [InvalidArgumentException::class],
                'useIgnoreLogic'             => false,
                'useFacade'                  => true,
                'loggerErrorHandler'         => '',
            ],
            'handlers' => $loggerHandler
        ];

        $this->s3Client = new S3Client($config['s3']);
        $this->lotTransmitter = new LotTransmitter($config);

    }

    /**
     * @param \FunctionalTester $i
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \LotTransmitter\Exception\InvalidRequestException
     */
    public function s3ProcessTest(\FunctionalTester $i): void
    {
        $i->wantToTest('that files upload in S3 bucket are processed by the service');


        $importFiles = $this->getFiles(__DIR__ . '/test_data/s3_valid_files');

        foreach ($importFiles as $fileName => $content) {
            $this->createTestFile(self::S3_BUCKET_NAME, 'import/' . $fileName, $content);
        }

        $this->lotTransmitter->transmitFiles();

    }


    /**
     * remove directory and all files
     *
     * @param $directory
     */
    private function deleteFiles($directory)
    {
        $it = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($directory);
    }

    /**
     * @param $directory
     * @return array
     */
    private function getFiles($directory)
    {
        $result = [];
        $it = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            } else {
                $result[$file->getFileName()] = file_get_contents($file->getRealPath());
            }
        }

        return $result;
    }

    /**
     * @param $bucket
     * @param $fileName
     * @param $content
     */
    private function createTestFile($bucket, $fileName, $content)
    {
        $this->s3Client->putObject(
            [
                'Bucket' => $bucket,
                'Key' => $fileName,
                'Body' => $content,
            ]
        );
    }
}
