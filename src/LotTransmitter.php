<?php

namespace LotTransmitter;

use Aws\S3\S3Client;
use GuzzleHttp\Client;
use JiraRestApi\JiraException;
use JsonMapper_Exception;
use LotTransmitter\Exception\InvalidContractException;
use LotTransmitter\Exception\InvalidOperationException;
use LotTransmitter\Exception\LogBaseDataException;
use LotTransmitter\ValueObject\Endpoint;
use Throwable;

/**
 * Class LotTransmitter
 * @package LotTransmitter
 */
class LotTransmitter
{
    /** @var HttpClient */
    private $httpClient;

    /** @var XmlParser */
    private $xmlParser;

    /** @var S3Service */
    private $s3Service;

    /** @var JiraService */
    private $jiraService;

    /**
     * LotTransmitter constructor.
     * @param array $config
     * @throws Exception\InvalidEndpointException
     * @throws JiraException
     */
    public function __construct(array $config)
    {
        $this->s3Service = new S3Service(new S3Client($config['s3']), $config['s3bucket'], $config['s3prefix']);
        $this->httpClient = new HttpClient(
            new Client(['headers' => ['Content-Type' => 'application/json']]),
            new Endpoint($config['restEndpointUrl'])
        );
        $this->xmlParser = new XmlParser();
        $this->jiraService = new JiraService($config['jira']);
    }

    /**
     *
     * @return void
     * @throws JiraException
     * @throws JsonMapper_Exception
     * @throws Throwable
     */
    public function transmitFiles(): void
    {
        $fileNames = $this->s3Service->getXmlFileNames();

        if (count($fileNames) === 0) {
            Logger::debug(
                'No file found to transmit'
            );
        }

        foreach ($fileNames as $fileName) {
            $xmlFileContent = $this->s3Service->getFileContent($fileName);
            try {
                $lot = $this->xmlParser->getLot($xmlFileContent);
                $this->httpClient->sendLot($lot);
                $this->s3Service->moveFileToArchive($fileName);

                Logger::info(
                    'Transmitted file successful',
                    [
                        'fileName' => $fileName,
                    ]
                );
            } catch (InvalidContractException $e) {
                $this->s3Service->moveFileToError($fileName);

                $header = sprintf('Lot "%s" konnte nicht erfolgreich eingelesen werden', $fileName);
                $description = sprintf(
                    'Beim Einlesen der Datei _%s_ ist ein Fehler aufgetreten. Grund: "%s" ',
                    $fileName,
                    $e->getMessage()
                );

                $ticket = $this->jiraService->createNewTicket($header, $description);

                Logger::error(
                    'Data contract violation',
                    [
                        'ticket' => $ticket,
                        'fileName' => $fileName,
                        'errorMessage' => $e->getMessage(),
                    ]
                );
            } catch (InvalidOperationException\ArticleAlreadyKnownException $e) {
                $this->s3Service->moveFileToError($fileName);

                Logger::error(
                    'LogBase data violation',
                    [
                        'fileName'     => $fileName,
                        'errorMessage' => $e->getMessage(),
                    ]
                );
            } catch (LogBaseDataException $e) {
                $this->s3Service->moveFileToError($fileName);

                $header = sprintf('Lot "%s" konnte nicht an LogBase übertragen werden', $fileName);
                $description = sprintf(
                    'Beim Übertragen des Lots aus der Datei _%s_ ist ein Fehler aufgetreten. Grund: "%s" ',
                    $fileName,
                    $e->getMessage()
                );

                $ticket = $this->jiraService->createNewTicket($header, $description);

                Logger::error(
                    'LogBase data violation',
                    [
                        'ticket'       => $ticket,
                        'fileName'     => $fileName,
                        'errorMessage' => $e->getMessage(),
                    ]
                );
            } catch (Throwable $e) {
                $this->s3Service->moveFileToError($fileName);

                throw $e;
            }
        }
    }
}