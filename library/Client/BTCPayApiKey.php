<?php
declare(strict_types=1);
namespace Coinsnap\Client;

if (!defined('ABSPATH')) {
    exit;
}

class BTCPayApiKey extends AbstractClient{

//  Create a URL you can send the user to. He/she will be prompted to create an API key that corresponds with your needs.    
    public static function getAuthorizeUrl(string $baseUrl, array $permissions, ?string $applicationName, ?bool $strict, ?bool $selectiveStores, ?string $redirectToUrlAfterCreation, ?string $applicationIdentifier): string
    {
        $url = rtrim($baseUrl, '/') . '/api-keys/authorize';

        $params = [];
        $params['permissions'] = $permissions;
        $params['applicationName'] = $applicationName;
        $params['strict'] = $strict;
        $params['selectiveStores'] = $selectiveStores;
        $params['redirect'] = $redirectToUrlAfterCreation;
        $params['applicationIdentifier'] = $applicationIdentifier;

        // Take out NULL values
        $params = array_filter($params, function ($value) {
            return $value !== null;
        });

        $queryParams = [];

        foreach ($params as $param => $value) {
            if ($value === true) {
                $value = 'true';
            }
            if ($value === false) {
                $value = 'false';
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === true) {
                        $item = 'true';
                    }
                    if ($item === false) {
                        $item = 'false';
                    }
                    $queryParams[] = $param . '=' . urlencode((string)$item);
                }
            } else {
                $queryParams[] = $param . '=' . urlencode((string)$value);
            }
        }

        $queryParams = implode("&", $queryParams);
        $url .= '?' . $queryParams;
        return $url;
    }

    /**
     * Permissions of this client's API key as reported by the server, or null if the key is rejected.
     * @throws \Coinsnap\Exception\ConnectException on network errors
     */
    public function getCurrentPermissions(): ?array
    {
        $url = $this->getApiUrl() . 'api-keys/current';
        $response = $this->getHttpClient()->request('GET', $url, $this->getRequestHeaders());
        if ($response->getStatus() !== 200) {
            return null;
        }
        $data = json_decode($response->getBody(), true);
        return (is_array($data) && is_array($data['permissions'] ?? null)) ? $data['permissions'] : null;
    }
}
