<?php

declare(strict_types=1);

namespace Coinsnap\Result;

use Coinsnap\Util\PreciseNumber;

class StoreOnChainWalletTransactionDestination extends AbstractResult
{
    public function getDestination(): string
    {
        $data = $this->getData();
        return $data['destination'];
    }

    public function getAmount(): PreciseNumber
    {
        $data = $this->getData();
        return PreciseNumber::parseString($data['amount']);
    }

    public function subtractFromAmount(): bool
    {
        $data = $this->getData();
        return $data['subtractFromAmount'];
    }
}
