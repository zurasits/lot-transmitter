<?php

namespace LotTransmitter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use LotTransmitter\Exception\InvalidOperationException;
use LotTransmitter\Exception\InvalidRequestException;
use LotTransmitter\Exception\UnknownGtinException;
use LotTransmitter\ValueObject\Endpoint;
use LotTransmitter\ValueObject\Lot;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HttpClient
 * @package LotTransmitter
 */
class HttpClient
{
    /** @var Endpoint */
    private $url;

    /** @var Client */
    private $client;

    /**
     * HttpClient constructor.
     * @param Client $client
     * @param Endpoint $url
     */
    public function __construct(Client $client, Endpoint $url)
    {
        $this->client = $client;
        $this->url = $url;
    }

    /**
     * @param Lot $lot
     *
     * @return void
     * @throws UnknownGtinException
     *
     * @throws InvalidRequestException
     * @throws UnknownGtinException
     * @throws InvalidOperationException
     * @throws InvalidOperationException\ArticleAlreadyKnownException
     */
    public function sendLot(Lot $lot): void
    {
        try {
            $response = $this->client->post(
                $this->url->value(),
                [
                    'body' => json_encode($lot)
                ]
            );
        } catch (RequestException $exception) {
            $exceptionResponse = $exception->getResponse();
            if($exceptionResponse instanceof ResponseInterface) {
                $responseBody = json_decode($exceptionResponse->getBody(), true);
                if ($responseBody['title'] === 'LOOPArgumentException') {

                    throw new UnknownGtinException(implode("\n", $responseBody['details']));
                }
                if ($responseBody['title'] === 'InvalidOperationException') {
                    $message = implode("\n", $responseBody['details']);

                    if(preg_match('/InvalidOperationException: Der Artikel \[\d+\] ist bereits im System vorhanden\./', $message)) {
                        throw new InvalidOperationException\ArticleAlreadyKnownException($message);
                    }

                    throw new InvalidOperationException($message);
                }
            }

            throw $exception;
        }

        if ((int)($response->getStatusCode() / 100) !== 2) {
            throw new InvalidRequestException('invalid request for lot payload with uoms');
        }
    }
}