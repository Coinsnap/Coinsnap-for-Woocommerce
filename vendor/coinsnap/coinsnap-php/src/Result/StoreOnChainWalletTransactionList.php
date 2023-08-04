<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class StoreOnChainWalletTransactionList extends AbstractListResult {
    //  @return \Coinsnap\Result\StoreOnChainWalletTransaction[]
    public function all(): array
    {
        $storeWalletTransactions = [];
        foreach ($this->getData() as $storeWalletTransaction) {
            $storeWalletTransactions[] = new \Coinsnap\Result\StoreOnChainWalletTransaction($storeWalletTransaction);
        }
        return $storeWalletTransactions;
    }
}
