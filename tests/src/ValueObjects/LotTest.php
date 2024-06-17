<?php

namespace LotTransmitterTest\ValueObjects;

use LotTransmitter\Exception\InvalidLotException;
use LotTransmitter\ValueObject\Gtin;
use LotTransmitter\ValueObject\Item;
use LotTransmitter\ValueObject\Lot;
use LotTransmitter\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

class LotTest extends TestCase
{

    /**
     *
     * @throws \LotTransmitter\Exception\InvalidGtinException
     * @throws \LotTransmitter\Exception\InvalidLotException
     * @throws \LotTransmitter\Exception\InvalidQuantityException
     *
     * @return void
     */
    public function testItemHaveValidFields()
    {
        $expectedItem = new Item(new Gtin(4251200680257), new Quantity(3));
        $actualLot = new Lot(new Gtin(4251200680257), [$expectedItem]);
        $this->assertIsArray($actualLot->items());
        $this->assertInstanceOf(Item::class, $actualLot->items()[0]);
    }

    /**
     *
     * @throws InvalidLotException
     * @throws \LotTransmitter\Exception\InvalidGtinException
     *
     * @return void
     */
    public function testLotItemIsEmpty()
    {
        $this->expectException(InvalidLotException::class);
        new Lot(new Gtin(4251200680257), []);
    }

}
