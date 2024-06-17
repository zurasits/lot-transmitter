<?php

namespace LotTransmitter\ValueObject;

use LotTransmitter\Exception\InvalidQuantityException;

/**
 * Class Quantity
 * @package LotTransmitter\ValueObject
 */
class Quantity implements \JsonSerializable
{
    /**
     * @var int
     */
    private $value;

    /**
     * Quantity constructor.
     * @param string $value
     * @throws InvalidQuantityException
     */
    public function __construct(string $value)
    {
        $this->validate($value);

        $this->value = (int)$value;
    }

    /**
     * @param string $value
     *
     * @throws InvalidQuantityException
     *
     * @return void
     */
    private function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidQuantityException(sprintf('Quantity %s can not be null', $value));
        }

        if (!is_numeric($value)) {
            throw new InvalidQuantityException(sprintf('Quantity contains non numeric character %s', $value));
        }

        if(preg_match('/[\D]/', $value)){
            throw new InvalidQuantityException(sprintf('Quantity is not an integer %s', $value));
        }

        if ($value < 1) {
            throw new InvalidQuantityException(sprintf('Quantity %s is not positive', $value));
        }
    }

    /**
     * @return int
     */
    public function value(): int
    {
        return $this->value;
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