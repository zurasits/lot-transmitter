<?php

namespace LotTransmitterTest\ValueObjects;

use LotTransmitter\Exception\InvalidQuantityException;
use LotTransmitter\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class QuantityTest
 */
class QuantityTest extends TestCase
{

    /**
     *
     * @throws InvalidQuantityException
     *
     * @return void
     */
    public function testGtinIsValidIntValueGiven()
    {
        $quantity = new Quantity(6);
        $this->assertEquals(6, $quantity->value());
    }

    /**
     *
     * @throws InvalidQuantityException
     *
     * @return void
     */
    public function testQuantityIsValidStringValueGiven()
    {
        $quantity = new Quantity('6');
        $this->assertEquals(6, $quantity->value());
    }

    /**
     *
     * @throws InvalidQuantityException
     *
     * @return void
     */
    public function testInvalidQuantityValueIsFloatString()
    {
        $this->expectException(InvalidQuantityException::class);
        new Quantity('6,7');
    }

    /**
     * @throws InvalidQuantityException
     */
    public function testInvalidQuantityValueIsNull()
    {
        $this->expectException(TypeError::class);
        new Quantity(null);
    }

    /**
     * @throws InvalidQuantityException
     */
    public function testInvalidQuantityExceptionValueIsLessThanOne()
    {
        $this->expectException(InvalidQuantityException::class);
        new Quantity(0);
    }

    /**
     *
     * @throws InvalidQuantityException
     *
     * @return void
     */
    public function testInvalidQuantityValueIsNegativ()
    {
        $this->expectException(InvalidQuantityException::class);
        new Quantity(-2);
    }

}