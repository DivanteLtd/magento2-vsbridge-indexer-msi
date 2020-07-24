<?php
/**
 * @package   Divante\VsbridgeIndexerMsi
 * @author    Joel Rainwater <joel.rainwater@netatmo.com>
 * @copyright 2019 Divante Sp. z o.o.
 * @license   See LICENSE_DIVANTE.txt for license details.
 */

declare(strict_types=1);

namespace Divante\VsbridgeIndexerMsi\Api;

/**
 * Service which returns linked stock Id for a store id
 */
interface GetStockIdByStoreIdInterface
{
    /**
     * @param int $storeId
     *
     * @return int
     */
    public function execute(int $storeId): int;
}
