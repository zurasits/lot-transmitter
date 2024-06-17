<?php

namespace LotTransmitter\ValueObject;

use LotTransmitter\Exception\InvalidGtinException;

/**
 * Class Gtin
 * @package LotTransmitter\ValueObject
 */
class Gtin implements \JsonSerializable
{
    /** @var int */
    private $gtin;

    /**
     * Gtin constructor.
     * @param string $gtin
     * @throws InvalidGtinException
     */
    public function __construct(string $gtin)
    {
        $this->validateLength($gtin);
        $this->validateChecksum($gtin);

        $this->gtin = (int)$gtin;
    }

    /**
     * @return int
     */
    public function value(): int
    {
        return $this->gtin;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->gtin;
    }

    /**
     * @param string $gtin
     *
     * @throws InvalidGtinException
     *
     * @return void
     */
    private function validateLength(string $gtin): void
    {
        if (!in_array(strlen($gtin), [8, 12, 13, 14, 17, 18], true)) {
            throw new InvalidGtinException(sprintf('GTIN length is invalid. "%d"', $gtin));
        }
    }

    /**
     * @param string $gtin
     *
     * @throws InvalidGtinException
     *
     * @return void
     */
    private function validateChecksum(string $gtin): void
    {
        /** @var int[] $gtinElements */
        $gtinElements = str_split($gtin);

        $givenChecksum = array_pop($gtinElements);
        if (!is_numeric($givenChecksum)) {
            throw new InvalidGtinException(sprintf('Gtin contains non numeric character %s', $givenChecksum));
        }

        $gtinElements = array_reverse($gtinElements);
        $sum = 0;

        foreach ($gtinElements as $position => $element) {
            if (!is_numeric($element)) {
                throw new InvalidGtinException(sprintf('Gtin contains non numeric character %s', $element));
            }
            $factor = ($position % 2) ? 1 : 3; // even : odd
            $sum += $factor * $element;
        }

        $calculatedChecksum = (10 - ($sum % 10)) % 10;

        if (((int)$givenChecksum) !== $calculatedChecksum) {
            throw new InvalidGtinException(sprintf('GTIN checksum does not match. "%d"', $gtin));
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->value();
    }
}