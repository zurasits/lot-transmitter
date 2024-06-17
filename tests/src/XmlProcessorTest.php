<?php

namespace LotTransmitterTest;

use LotTransmitter\Exception\InvalidGtinException;
use LotTransmitter\Exception\InvalidQuantityException;
use LotTransmitter\Exception\NoXmlFormatException;
use LotTransmitter\XmlParser;
use PHPUnit\Framework\TestCase;

/**
 * Class XmlProcessorTest
 * @package LotTransmitter
 */
class XmlProcessorTest extends TestCase
{

    /** @var array */
    private $xml;
    /** @var XmlParser */
    private $parser;
    /** @var string */
    private $xmlString;

    /**
     *
     * @return void
     */
    public function setUp()
    {
        $this->xml = [
            'testcaseValid'         =>  __DIR__ . '/data/testcaseValid.xml',
            'testcaseGtinIsEmpty'       => __DIR__ . '/data/testcaseGtinIsEmpty.xml',
            'testcaseInvalidXml'    =>  __DIR__ . '/data/testcaseInvalidXml.xml',
        ];
        $this->parser = new XmlParser();
    }

    /**
     * @param $xml
     *
     * @return false|string
     */
    public function getTestData($xml)
    {
        return file_get_contents($xml);
    }

    /**
     *
     * @throws InvalidGtinException
     * @throws InvalidQuantityException
     * @throws NoXmlFormatException
     * @throws \LotTransmitter\Exception\InvalidLotException
     *
     * @return void
     */
    public function testXmlParserReturnsValidClassAndData()
    {
        $this->xmlString = $this->getTestData($this->xml['testcaseValid']);
        $actualLot = $this->parser->getLot($this->xmlString);
        $this->assertArrayHasKey('gtin', $actualLot->jsonSerialize());
        $this->assertArrayHasKey('items', $actualLot->jsonSerialize());
    }

    /**
     *
     * @throws InvalidGtinException
     * @throws InvalidQuantityException
     * @throws NoXmlFormatException
     * @throws \LotTransmitter\Exception\InvalidLotException
     *
     * @return void
     */
    public function testXmlWithEmptyAndInvalidData()
    {
        $this->xmlString = $this->getTestData($this->xml['testcaseGtinIsEmpty']);
        $this->expectException(InvalidGtinException::class);
        $this->parser->getLot($this->xmlString);
    }

    /**
     *
     * @throws InvalidGtinException
     * @throws InvalidQuantityException
     * @throws NoXmlFormatException
     * @throws \LotTransmitter\Exception\InvalidLotException
     *
     * @return void
     */
    public function testXMLHasInvalidStructure()
    {
        $this->xmlString = $this->getTestData($this->xml['testcaseInvalidXml']);
        $this->expectException(NoXmlFormatException::class);
        $this->parser->getLot($this->xmlString);
    }
}