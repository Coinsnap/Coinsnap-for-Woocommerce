<?php

declare(strict_types=1);

namespace Coinsnap\Client;

class Miscellaneous extends AbstractClient
{
    public function getPermissionMetadata(): \Coinsnap\Result\PermissionMetadata
    {
        $url = $this->getBaseUrl() . '/misc/permissions';
        $headers = $this->getRequestHeaders();
        $method = 'GET';

        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\PermissionMetadata(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    public function getLanguageCodes(): \Coinsnap\Result\LanguageCodeList
    {
        $url = $this->getBaseUrl() . '/misc/lang';
        $headers = $this->getRequestHeaders();
        $method = 'GET';

        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\LanguageCodeList(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    public function getInvoiceCheckout(
        string $invoiceId,
        ?string $lang
    ): \Coinsnap\Result\InvoiceCheckoutHTML {
        $url = $this->getBaseUrl() . '/i/' . urlencode($invoiceId);

        //set language query parameter if passed
        if (isset($lang)) {
            $url .= '?lang=' . $lang;
        }

        $headers = $this->getRequestHeaders();
        $method = 'GET';

        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\InvoiceCheckoutHTML(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }
}
