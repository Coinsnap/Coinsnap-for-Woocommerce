<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class PullPaymentList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\PullPayment[]
     */
    public function all(): array
    {
        $pullPayments = [];
        foreach ($this->getData() as $pullPaymentData) {
            $pullPayments[] = new \Coinsnap\Result\PullPayment($pullPaymentData);
        }
        return $pullPayments;
    }
}
