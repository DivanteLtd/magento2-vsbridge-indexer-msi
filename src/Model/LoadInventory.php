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
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;

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
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var IsSourceItemManagementAllowedForSkuInterface
     */
    private $isSourceItemManagementAllowedForSku;

    /**
     * LoadChildrenInventory constructor.
     *
     * @param InventoryResource $resource
     * @param GetStockIdByStoreIdInterface $getStockIdByStoreId
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
     */
    public function __construct(
        InventoryResource $resource,
        GetStockIdByStoreIdInterface $getStockIdByStoreId,
        GetProductSalableQtyInterface $getProductSalableQty,
        IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
    ) {
        $this->resource = $resource;
        $this->getStockIdByStoreId = $getStockIdByStoreId;
        $this->getProductSalableQty = $getProductSalableQty;
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
                ? $this->getProductSalableQty->execute($sku, $stockId)
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
}
