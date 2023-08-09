<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class WebhookDeliveryList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\WebhookDelivery[]
     */
    public function all(): array
    {
        $webhookDeliveries = [];
        foreach ($this->getData() as $webhookDelivery) {
            $webhookDeliveries[] = new \Coinsnap\Result\WebhookDelivery($webhookDelivery);
        }
        return $webhookDeliveries;
    }
}
