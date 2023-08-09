<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class StoreOnChainWalletUTXOList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\StoreOnChainWalletUTXO[]
     */
    public function all(): array
    {
        $storeWalletUTXOs = [];
        foreach ($this->getData() as $storeWalletUTXO) {
            $storeWalletUTXOs[] = new \Coinsnap\Result\StoreOnChainWalletUTXO($storeWalletUTXO);
        }
        return $storeWalletUTXOs;
    }
}
