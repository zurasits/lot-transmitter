<?php

namespace LotTransmitter\ValueObject;

use JsonSerializable;
use LotTransmitter\Exception\InvalidLotException;

/**
 * Class Lot
 * @package LotTransmitter\ValueObject
 */
class Lot implements JsonSerializable
{
    /** @var Gtin */
    private $gtin;

    /** @var array|Item[] */
    private $items = [];

    /**
     * Lot constructor.
     * @param Gtin $gtin
     * @param array $items
     * @throws InvalidLotException
     */
    public function __construct(Gtin $gtin, array $items)
    {
        $this->assertItemArray($items);
        $this->gtin = $gtin;
        $this->items = $items;
    }

    /**
     * @return Gtin
     */
    public function gtin(): Gtin
    {
        return $this->gtin;
    }

    /**
     * @return Item[]
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     *
     * @throws InvalidLotException
     *
     * @return void
     */
    private function assertItemArray(array $items): void
    {
        if (empty($items)) {
            throw new InvalidLotException('item is empty');
        }

        foreach ($items as $item) {
            if (!$item instanceof Item) {
                throw new InvalidLotException('invalid item');
            }
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
        return [
            'gtin' => $this->gtin,
            'articles' => $this->items,
        ];
    }
}