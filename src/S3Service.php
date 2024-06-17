<?php

namespace LotTransmitter;

use Aws\S3\S3Client;
use GuzzleHttp\Psr7\Stream;
use function count;

/**
 * Class S3Service
 * @package LotTransmitter
 */
class S3Service
{
    private const DIR_IMPORT = 'import';
    private const DIR_ARCHIVE = 'archive';
    private const DIR_ERROR = 'error';

    /**
     * @var S3Client
     */
    private $s3Client;

    /**
     * @var string
     */
    private $bucket;

    /**
     * @var string|null
     */
    private $prefix;

    /**
     * S3Service constructor.
     * @param S3Client $s3Client
     * @param string|null $bucket
     * @param string|null $prefix
     */
    public function __construct(S3Client $s3Client, string $bucket, ?string $prefix)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
        if ($prefix) {
            $this->prefix = $prefix . '/';
        } else {
            $this->prefix = '';
        }
    }

    /**
     * @return array
     */
    public function getXmlFileNames(): array
    {
        $objects = $this->s3Client->listObjects(
            [
                'Bucket' => $this->bucket,
                'Prefix' => $this->prefix . self::DIR_IMPORT,
            ]
        );

        if (!$objects->hasKey('Contents')) {
            return [];
        }

        $filesContained = $objects->get('Contents');

        if (!count($filesContained)) {
            return [];
        }

        $keys = [];
        foreach ($filesContained as $res) {
            if (strpos($res['Key'], '.xml')) {
                if (!empty($this->prefix)) {
                    // clean from path prefix
                    $keys[] = preg_replace("~^{$this->prefix}~", '', $res['Key'], 1);
                } else {
                    $keys[] = $res['Key'];
                }
            }
        }

        return $keys;
    }

    /**
     * @param $key
     * @return string
     */
    public function getFileContent($key): string
    {
        $s3Object = $this->s3Client->getObject(
            [
                'Bucket' => $this->bucket,
                'Key'    => $this->prefix . $key,
            ]
        );

        /** @var Stream $body */
        $body = $s3Object->get('Body');

        return $body->getContents();
    }

    /**
     * @param $fileName
     */
    public function moveFileToArchive($fileName)
    {
        $this->moveFile($fileName, self::DIR_IMPORT, self::DIR_ARCHIVE);
    }

    /**
     * @param $fileName
     */
    public function moveFileToError($fileName)
    {
        $this->moveFile($fileName, self::DIR_IMPORT, self::DIR_ERROR);
    }

    /**
     * Moves file to another location in bucket
     *
     * @param string $sourceFilePath
     * @param string $currentPrefix
     * @param string $destPrefix
     */
    private function moveFile(string $sourceFilePath, string $currentPrefix, string $destPrefix): void
    {
        $destinationFilePath = str_replace($currentPrefix . '/', $destPrefix . '/', $sourceFilePath);

        $success = $this->s3Client->copyObject(
            [
                'Bucket'     => $this->bucket,
                'Key'        => $this->prefix . $destinationFilePath,
                'CopySource' => "{$this->bucket}/{$this->prefix}{$sourceFilePath}",
            ]
        );

        if ($success) {
            $this->s3Client->deleteObject(
                [
                    'Bucket' => $this->bucket,
                    'Key'    => $this->prefix . $sourceFilePath,
                ]
            );
        }
    }

}