<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class WebhookList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\Webhook[]
     */
    public function all(): array
    {
        $webhooks = [];
        foreach ($this->getData() as $webhook) {
            $webhooks[] = new \Coinsnap\Result\Webhook($webhook);
        }
        return $webhooks;
    }
}
