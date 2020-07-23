<?php
/**
 * @package   Divante\VsbridgeIndexerMsi
 * @author    Joel Rainwater <joel.rainwater@netatmo.com>
 * @copyright 2019 Divante Sp. z o.o.
 * @license   See LICENSE_DIVANTE.txt for license details.
 */

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Model;

use Divante\VsbridgeIndexerMsi\Api\GetStockIdByStoreIdInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class GetStockIdByStoreId
 */
class GetStockIdByStoreId implements GetStockIdByStoreIdInterface
{
    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteId;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Construct
     *
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        StoreManagerInterface $storeManager
    ) {
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function execute(int $storeId): int
    {
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteId->execute($websiteId)->getStockId();
        return $stockId;
    }
}
