<?php

namespace LotTransmitterTest\ValueObjects;

use LotTransmitter\Exception\InvalidGtinException;
use LotTransmitter\ValueObject\Gtin;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class GtinTest
 */
class GtinTest extends TestCase
{
    /**
     *
     * @throws InvalidGtinException
     *
     * @return void
     */
    public function testGtinIsValidIntValueGiven()
    {
        $gtin = new Gtin(4251200680257);
        $this->assertEquals(4251200680257, $gtin->value());
    }

    /**
     *
     * @throws InvalidGtinException
     *
     * @return void
     */
    public function testGtinIsValidStringValueGiven()
    {
        $gtin = new Gtin('4251200680257');
        $this->assertEquals(4251200680257, $gtin->value());
    }

    /**
     *
     * @throws InvalidGtinException
     *
     * @return void
     */
    public function testInvalidGtinValueIsNull()
    {
        $this->expectException(TypeError::class);
        new Gtin(null);
    }

    /**
     *
     * @throws InvalidGtinException
     *
     * @return void
     */
    public function testInvalidGtinValueIsNumericNull()
    {
        $this->expectException(InvalidGtinException::class);
        new Gtin(0);
    }

    /**
     *
     * @throws InvalidGtinException
     *
     * @return void
     */
    public function testInvalidGtinValueI()
    {
        $this->expectException(InvalidGtinException::class);
        new Gtin(123);
    }
}