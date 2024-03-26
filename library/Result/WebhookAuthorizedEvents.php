<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class WebhookAuthorizedEvents extends AbstractResult
{
    public function hasEverything(): bool
    {
        $data = $this->getData();
        return $data['everything'];
    }

    /**
     * @return array of strings
     */
    public function getSpecificEvents(): array
    {
        $data = $this->getData();
        return $data['specificEvents'];
    }
}
