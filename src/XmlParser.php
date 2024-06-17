<?php

namespace LotTransmitter;

use LotTransmitter\Exception\DuplicateGtinException;
use LotTransmitter\Exception\NoXmlFormatException;
use LotTransmitter\Exception\NoXmlItemException;
use LotTransmitter\Exception\NoXmlNodeException;
use LotTransmitter\ValueObject\Gtin;
use LotTransmitter\ValueObject\Item;
use LotTransmitter\ValueObject\Lot;
use LotTransmitter\ValueObject\Quantity;

/**
 * Class XmlParser
 * @package LotTransmitter
 */
class XmlParser
{
    /**
     * @param $content
     *
     * @return Lot
     * @throws Exception\InvalidGtinException
     * @throws Exception\InvalidLotException
     * @throws Exception\InvalidQuantityException
     * @throws NoXmlFormatException
     * @throws NoXmlItemException
     * @throws NoXmlNodeException
     *
     * @throws DuplicateGtinException
     */
    public function getLot($content): Lot
    {
        /** @var \SimpleXMLElement $xml */
        $xml = $this->getXmlObject($content);
        $this->assertXmlNodeExists($xml, 'Item');

        $assortmentDefinitionIndex = $this->searchItem($xml, 'Imp_AssortmentDefinition');
        $referenceItemDefinition = $this->searchItem($xml, 'Uoms');

        $this->assertXmlItemExists($xml->Item, 0);
        $this->assertXmlItemExists($xml->Item, 1);

        $this->assertXmlNodeExists($xml->Item[$assortmentDefinitionIndex], 'Imp_AssortmentDefinition');
        /** @var \SimpleXMLElement $assortmentDefinition */
        $assortmentDefinition = $xml->Item[$assortmentDefinitionIndex]->Imp_AssortmentDefinition;
        //item data
        $items = [];
        $gtins = [];
        foreach ($assortmentDefinition as $item) {
            //check gtin node
            $this->assertXmlNodeExists($item, 'VariantItem');
            $this->assertXmlNodeExists($item->VariantItem, 'eans');
            $this->assertXmlItemExists($item->VariantItem->eans, 0);

            //check quantity node
            $this->assertXmlNodeExists($item, 'quantity');
            $this->assertXmlNodeExists($item->quantity, 'amount');
            $items[] = new Item(
                new Gtin($item->VariantItem->eans[0]),
                new Quantity($item->quantity->amount)
            );

            //check duplicate gtin
            $this->assertUniqueGtins($item->VariantItem->eans[0], $gtins);
            $gtins[] = $item->VariantItem->eans[0];
        }

        /** @var \SimpleXMLElement $lotData */
        $lotData = $xml->Item[$referenceItemDefinition];

        $this->assertXmlNodeExists($lotData, 'Uoms');
        $this->assertXmlNodeExists($lotData->Uoms, 'EANs');
        $this->assertXmlNodeExists($lotData->Uoms->EANs, 'id');

        return new Lot(
            new Gtin($lotData->Uoms->EANs->id),
            $items
        );
    }

    /**
     * @param string $xmlContent
     * @return \SimpleXMLElement
     * @throws NoXmlFormatException
     */
    private function getXmlObject(string $xmlContent): \SimpleXMLElement
    {
        $xml = @\simplexml_load_string($xmlContent, "SimpleXMLElement", LIBXML_NOCDATA);

        if (!$xml instanceof \SimpleXMLElement) {
            throw new NoXmlFormatException('file is not a xml:' . $xmlContent);
        }
        return $xml;
    }

    /**
     * @param \SimpleXMLElement $xmlElement
     * @param int $index
     *
     * @return void
     * @throws NoXmlItemException
     *
     */
    private function assertXmlItemExists(\SimpleXMLElement $xmlElement, int $index): void
    {
        if (empty($xmlElement[$index])) {
            throw new NoXmlItemException(sprintf("xml Item with index %d does not exists", $index));
        }
    }

    /**
     * @param \SimpleXMLElement $xmlElement
     * @param string $node
     *
     * @return void
     * @throws NoXmlNodeException
     *
     */
    private function assertXmlNodeExists(\SimpleXMLElement $xmlElement, string $node): void
    {
        if (empty($xmlElement->$node)) {
            throw new NoXmlNodeException(sprintf('xml Node with name %s does not exists', $node));
        }
    }

    private function searchItem(\SimpleXMLElement $xmlElement, string $needle): ?int
    {
        $key = 0;
        foreach ($xmlElement as $item) {
            if (isset($item->$needle)
                && $item->$needle instanceof \SimpleXMLElement
                && count($item->$needle->children()) > 0) {

                return $key;
            }
            $key++;
        }

        return null;
    }

    /**
     * @param string $gtin
     * @param array $gtins
     *
     * @return void
     * @throws DuplicateGtinException
     */
    private function assertUniqueGtins(string $gtin, array $gtins): void
    {
        if (in_array($gtin, $gtins)) {
           throw new DuplicateGtinException(sprintf('duplicate gtin %s', $gtin));
        }
    }

}