<?php

namespace LotTransmitterTest\ValueObjects;

use LotTransmitter\ValueObject\Gtin;
use LotTransmitter\ValueObject\Item;
use LotTransmitter\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

/**
 * Class ItemTest
 */
class ItemTest extends TestCase
{

    /**
     *
     * @throws \LotTransmitter\Exception\InvalidGtinException
     * @throws \LotTransmitter\Exception\InvalidQuantityException
     *
     * @return void
     */
    public function testItemHaveValidFields()
    {
        $actualItem = new Item(new Gtin(4251200680257), new Quantity(3));
        $this->assertEquals(2, count($actualItem->jsonSerialize()));
        $this->assertArrayHasKey('gtin', $actualItem->jsonSerialize());
        $this->assertArrayHasKey('quantity', $actualItem->jsonSerialize());

        $actualItemArray = $actualItem->jsonSerialize();
        /** @var Gtin $actualGtin */
        $actualGtin = $actualItemArray['gtin'];
        $this->assertEquals(4251200680257, $actualGtin->value());

        /** @var Quantity $actualGtin */
        $actualQuantity = $actualItemArray['quantity'];
        $this->assertEquals(3, $actualQuantity->value());

    }

}
