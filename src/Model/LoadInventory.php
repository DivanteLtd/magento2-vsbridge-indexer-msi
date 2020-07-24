<?php
/**
 * @package  Divante\VsbridgeIndexerMsi
 * @author Agata Firlejczyk <afirlejczyk@divante.pl>
 * @copyright 2019 Divante Sp. z o.o.
 * @license See LICENSE_DIVANTE.txt for license details.
 */

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Model;

use Divante\VsbridgeIndexerCatalog\Api\LoadInventoryInterface;
use Divante\VsbridgeIndexerMsi\Api\GetStockIdByStoreIdInterface;
use Divante\VsbridgeIndexerMsi\Model\ResourceModel\Product\Inventory as InventoryResource;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventoryReservations\Model\ResourceModel\GetReservationsQuantity;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Class LoadInventory
 */
class LoadInventory implements LoadInventoryInterface
{
    /**
     * @var InventoryResource
     */
    private $resource;

    /**
     * @var GetStockIdByStoreIdInterface
     */
    private $getStockIdByStoreId;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetReservationsQuantity
     */
    private $getReservationsQuantity;

    /**
     * @var IsSourceItemManagementAllowedForSkuInterface
     */
    private $isSourceItemManagementAllowedForSku;

    /**
     * LoadChildrenInventory constructor.
     *
     * @param InventoryResource $resource
     * @param GetStockIdByStoreIdInterface $getStockIdByStoreId
     * @param GetStockItemConfigurationInterface $getStockItemConfig
     * @param GetReservationsQuantity $getReservationsQuantity
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
     */
    public function __construct(
        InventoryResource $resource,
        GetStockIdByStoreIdInterface $getStockIdByStoreId,
        GetStockItemConfigurationInterface $getStockItemConfig,
        GetReservationsQuantity $getReservationsQuantity,
        IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
    ) {
        $this->resource = $resource;
        $this->getStockIdByStoreId = $getStockIdByStoreId;
        $this->getStockItemConfiguration = $getStockItemConfig;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->isSourceItemManagementAllowedForSku = $isSourceItemManagementAllowedForSku;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $indexData, int $storeId): array
    {
        $productIdBySku = $this->getIdBySku($indexData);
        $rawInventory = $this->resource->loadInventory($storeId, array_keys($productIdBySku));
        $rawInventoryByProductId = [];
        $stockId = $this->getStockIdByStoreId->execute($storeId);

        foreach ($rawInventory as $sku => $productInventory) {
            $productInventory['salable_qty'] = $this->isSourceItemManagementAllowedForSku->execute($sku)
                ? $this->getSalableQty($sku, $stockId, (int)$productInventory['qty'])
                : (int)$productInventory['qty']; // default to qty value if source management not allowed
            $productId = $productIdBySku[$sku];
            $productInventory['product_id'] = $productId;
            unset($productInventory['sku']);
            $rawInventoryByProductId[$productId] = $productInventory;
        }

        return $rawInventoryByProductId;
    }

    /**
     * @param array $indexData
     *
     * @return array
     */
    private function getIdBySku(array $indexData): array
    {
        $idBySku = [];

        foreach ($indexData as $productId => $product) {
            $sku = $product['sku'];
            $idBySku[$sku] = $productId;
        }

        return $idBySku;
    }

    /**
     * Get Salable Quantity for given SKU and Stock
     *
     * @param string $sku
     * @param int $stockId
     * @param int $qty
     *
     * @return float
     */
    private function getSalableQty(string $sku, int $stockId, int $qty): float
    {
        $stockItemConfig = $this->getStockItemConfiguration->execute($sku, $stockId);
        $minQty = $stockItemConfig->getMinQty();
        $productQtyInStock = $qty
            + $this->getReservationsQuantity->execute($sku, $stockId)
            - $minQty;

        return $productQtyInStock;
    }
}
