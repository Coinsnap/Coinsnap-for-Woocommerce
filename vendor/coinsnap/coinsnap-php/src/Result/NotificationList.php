<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class NotificationList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\Notification[]
     */
    public function all(): array
    {
        $notifications = [];
        foreach ($this->getData() as $notification) {
            $notifications[] = new \Coinsnap\Result\Notification($notification);
        }
        return $notifications;
    }
}
