<?php

namespace LotTransmitter\ValueObject;


use LotTransmitter\Exception\InvalidEndpointException;

/**
 * Class Endpoint
 * @package LotTransmitter\ValueObject
 */
class Endpoint
{

    /** @var string  */
    private $url;

    /**
     * Endpoint constructor.
     * @param string $url
     * @throws InvalidEndpointException
     */
    public function __construct(string $url)
    {
        $this->checkEndpoint($url);
        $this->url = $url;
    }

    /**
     * @param string $url
     *
     * @throws InvalidEndpointException
     *
     * @return void
     */
    private function checkEndpoint(string $url): void
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidEndpointException('invalid endpoint string');
        }
    }

    /**
     *
     * @return string
     */
    public function value(): string
    {
        return $this->url;
    }

}