<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class StoreOnChainWalletTransactionDestinationList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\StoreOnChainWalletTransactionDestination[]
     */
    public function all(): array
    {
        $destinations = [];
        foreach ($this->getData() as $destination) {
            $destinations[] = new \Coinsnap\Result\StoreOnChainWalletTransactionDestination($destination);
        }
        return $destinations;
    }
}
