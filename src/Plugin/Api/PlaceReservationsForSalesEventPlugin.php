<?php declare(strict_types=1);
/**
 * @package Divante\VsbridgeIndexerMsi
 * @author Joel Rainwater <joel.rainwater@netatmo.com>
 * @copyright 2019 Divante Sp. z o.o.
 * @license See LICENSE_DIVANTE.txt for license details.
 */

namespace Divante\VsbridgeIndexerMsi\Plugin\Api;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\Product as ProductIndexer;
use Magento\Catalog\Model\ProductIdLocatorInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;

/**
 * Class PlaceReservationsForSalesEventPlugin
 */
class PlaceReservationsForSalesEventPlugin
{
    /**
     * @var ProductIndexer
     */
    private $indexer;

    /**
     * @var ProductIdLocatorInterface
     */
    private $productIdLocator;

    /**
     * PlaceReservationsForSalesEventPlugin constructor.
     *
     * @param ProductIndexer $indexer
     * @param ProductIdLocatorInterface $productIdLocator
     */
    public function __construct(ProductIndexer $indexer, ProductIdLocatorInterface $productIdLocator)
    {
        $this->indexer = $indexer;
        $this->productIdLocator = $productIdLocator;
    }

    /**
     * Around Execute so we have access to $items
     *
     * @param PlaceReservationsForSalesEventInterface $subject
     * @param callable $proceed
     * @param array $items
     * @param SalesChannelInterface $salesChannel
     * @param SalesEventInterface $salesEvent
     *
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function aroundExecute(
        PlaceReservationsForSalesEventInterface $subject,
        callable $proceed,
        array $items,
        SalesChannelInterface $salesChannel,
        SalesEventInterface $salesEvent
    ) {
        // continue with execution
        $proceed($items, $salesChannel, $salesEvent);

        // we have to get product ids from skus
        $skus = [];
        /** @var \Magento\InventorySalesApi\Api\Data\ItemToSellInterface $item */
        foreach ($items as $item) {
            $skus[] = $item->getSku();
        }
        $productIds = [];
        foreach ($this->productIdLocator->retrieveProductIdsBySkus($skus) as $idsBySku) {
            array_push($productIds, ...array_keys($idsBySku));
        }

        // reindex after reservation has been placed
        $this->indexer->executeList($productIds);
    }
}
