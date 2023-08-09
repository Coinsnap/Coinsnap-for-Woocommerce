<?php

declare(strict_types=1);

namespace Coinsnap\Result;

class LanguageCodeList extends AbstractListResult
{
    /**
     * @return \Coinsnap\Result\LanguageCode[]
     */
    public function all(): array
    {
        $languageCodes = [];
        foreach ($this->getData() as $languageCode) {
            $languageCodes[] = new \Coinsnap\Result\LanguageCode($languageCode);
        }
        return $languageCodes;
    }
}
