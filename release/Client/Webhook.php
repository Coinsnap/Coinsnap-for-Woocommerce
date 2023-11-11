<?php

declare(strict_types=1);

namespace Coinsnap\Client;

class Webhook extends AbstractClient
{
    /**
     * @param string $storeId
     * @return \Coinsnap\Result\WebhookList
     */
    public function getStoreWebhooks(string $storeId): \Coinsnap\Result\WebhookList
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks';
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return new \Coinsnap\Result\WebhookList(
                json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
            );
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    public function getWebhook(string $storeId, string $webhookId): \Coinsnap\Result\Webhook
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId);
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new \Coinsnap\Result\Webhook($data);
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    public function getLatestDeliveries(string $storeId, string $webhookId, string $count): \Coinsnap\Result\WebhookDeliveryList
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId) . '/deliveries?count=' . $count;
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new \Coinsnap\Result\WebhookDeliveryList($data);
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    public function getDelivery(string $storeId, string $webhookId, string $deliveryId): \Coinsnap\Result\WebhookDelivery
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId) . '/deliveries/' . urlencode($deliveryId);
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new \Coinsnap\Result\WebhookDelivery($data);
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    /**
     * Get the delivery's request.
     *
     * The delivery's JSON request sent to the endpoint.
     *
     * @param string $storeId
     * @param string $webhookId
     * @param string $deliveryId
     * @return string JSON request
     */
    public function getDeliveryRequest(string $storeId, string $webhookId, string $deliveryId): string
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId) . '/deliveries/' . urlencode($deliveryId);
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return $response->getBody();
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    /**
     * Redeliver the delivery.
     *
     * @param string $storeId
     * @param string $webhookId
     * @param string $deliveryId
     * @return string The new delivery id being broadcasted. (Broadcast happen asynchronously with this call)
     */
    public function redeliverDelivery(string $storeId, string $webhookId, string $deliveryId): string
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' .
                        urlencode($webhookId) . '/deliveries/' . urlencode($deliveryId) . '/redeliver';
        $headers = $this->getRequestHeaders();
        $method = 'POST';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    public function createWebhook(string $storeId, string $url, ?array $specificEvents, ?string $secret): \Coinsnap\Result\WebhookCreated { //bool $enabled = true,bool $automaticRedelivery = true
        $data = [
            //'enabled' => $enabled,
            //'automaticRedelivery' => $automaticRedelivery,
            'url' => $url
        ];

        if ($specificEvents === null) {
            $data['authorizedEvents'] = ['everything' => true];
        } 
        elseif (count($specificEvents) === 0) {
            throw new \InvalidArgumentException('Argument $specificEvents should be NULL or contains at least 1 item.');
        } 
        else {
            $data['authorizedEvents'] = ['everything' => false,'specificEvents' => $specificEvents];
        }

        if ($secret === '') {
            throw new \InvalidArgumentException('Argument $secret should be NULL (let Server auto-generate a secret) or you should provide a long and safe secret string.');
        } 
        elseif ($secret !== null) $data['secret'] = $secret;

        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks';
        $headers = $this->getRequestHeaders();
        $method = 'POST';
        $response = $this->getHttpClient()->request($method, $url, $headers, json_encode($data, JSON_THROW_ON_ERROR));

        if ($response->getStatus() === 200) {
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new \Coinsnap\Result\WebhookCreated($data);
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    /**
     * Updates an existing webhook.
     *
     * @return \Coinsnap\Result\Webhook
     * @throws \JsonException
     */
    public function updateWebhook(
        string $storeId,
        string $url,
        string $webhookId,
        ?array $specificEvents,
        bool $enabled = true,
        bool $automaticRedelivery = true,
        ?string $secret = null
    ): \Coinsnap\Result\Webhook {
        $data = [
          'enabled' => $enabled,
          'automaticRedelivery' => $automaticRedelivery,
          'url' => $url,
          'secret' => $secret
        ];

        // Specific events or all.
        if ($specificEvents === null) {
            $data['authorizedEvents'] = [
              'everything' => true
            ];
        } elseif (count($specificEvents) === 0) {
            throw new \InvalidArgumentException('Argument $specificEvents should be NULL or contains at least 1 item.');
        } else {
            $data['authorizedEvents'] = [
              'everything' => false,
              'specificEvents' => $specificEvents
            ];
        }

        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId);
        $headers = $this->getRequestHeaders();
        $method = 'PUT';
        $response = $this->getHttpClient()->request($method, $url, $headers, json_encode($data, JSON_THROW_ON_ERROR));

        if ($response->getStatus() === 200) {
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            return new \Coinsnap\Result\Webhook($data);
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    //  Check if the request your received from a webhook is authentic and can be trusted.
    public static function isIncomingWebhookRequestValid(string $requestBody, string $coinsnapSignatureHeader, string $secret): bool{
        if ($requestBody && $coinsnapSignatureHeader) {            
            $expectedHeader = 'sha256='.hash_hmac('sha256', $requestBody, $secret);
            if ($expectedHeader === $coinsnapSignatureHeader) {
                return true;
            }
        }
        return false;
    }

    public function deleteWebhook(string $storeId, string $webhookId): void
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks/' . urlencode($webhookId);
        $headers = $this->getRequestHeaders();
        $method = 'DELETE';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() !== 200) {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }

    /**
     * @deprecated 2.0.0 Please use `getStoreWebhooks()` instead.
     * @see getStoreWebhooks()
     *
     * @param string $storeId
     * @return \Coinsnap\Result\Webhook[]
     */
    public function getWebhooks(string $storeId): array
    {
        $url = $this->getApiUrl() . ''.COINSNAP_SERVER_PATH.'/' . urlencode($storeId) . '/webhooks';
        $headers = $this->getRequestHeaders();
        $method = 'GET';
        $response = $this->getHttpClient()->request($method, $url, $headers);

        if ($response->getStatus() === 200) {
            $r = [];
            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            foreach ($data as $item) {
                $item = new \Coinsnap\Result\Webhook($item);
                $r[] = $item;
            }
            return $r;
        } else {
            throw $this->getExceptionByStatusCode($method, $url, $response);
        }
    }
}
