<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class LightningChannelList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\LightningChannel[]
     */
    public function all(): array
    {
        $channels = [];
        foreach ($this->getData() as $channel) {
            $channels[] = new \Coinsnap\Result\LightningChannel($channel);
        }
        return $channels;
    }
}
