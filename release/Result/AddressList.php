<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class AddressList extends \Coinsnap\Result\AbstractListResult
{
    public function all(): array
    {
        $r = [];
        foreach ($this->getData()['addresses'] as $addressData) {
            $r[] = new \Coinsnap\Result\Address($addressData);
        }
        return $r;
    }

    /**
     * @deprecated 2.0.0 Please use `all()` instead.
     * @see all()
     *
     * @return \Coinsnap\Result\Address[]
     */
    public function getAddresses(): array
    {
        $r = [];
        foreach ($this->getData()['addresses'] as $addressData) {
            $r[] = new \Coinsnap\Result\Address($addressData);
        }
        return $r;
    }
}
