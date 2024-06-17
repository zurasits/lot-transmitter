<?php

namespace LotTransmitter\ValueObject;

use JsonSerializable;

/**
 * Class Item
 * @package LotTransmitter\ValueObject
 */
class Item implements JsonSerializable
{

    /** @var Gtin */
    private $gtin;

    /** @var Quantity */
    private $quantity;

    /**
     * Item constructor.
     * @param Gtin $gtin
     * @param Quantity $quantity
     */
    public function __construct(Gtin $gtin, Quantity $quantity)
    {
        $this->gtin = $gtin;
        $this->quantity = $quantity;
    }

    /**
     * @return Gtin
     */
    public function gtin(): Gtin
    {
        return $this->gtin;
    }

    /**
     * @return Quantity
     */
    public function quantity(): Quantity
    {
        return $this->quantity;
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
        return [
            'quantity' => $this->quantity,
            'gtin' => $this->gtin,
        ];
    }
}