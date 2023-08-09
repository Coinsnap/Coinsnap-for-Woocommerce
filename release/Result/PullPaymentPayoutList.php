<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class PullPaymentPayoutList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\PullPaymentPayout[]
     */
    public function all(): array
    {
        $pullPaymentPayouts = [];
        foreach ($this->getData() as $pullPaymentPayoutData) {
            $pullPaymentPayouts[] = new \Coinsnap\Result\PullPaymentPayout($pullPaymentPayoutData);
        }
        return $pullPaymentPayouts;
    }
}
