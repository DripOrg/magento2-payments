<?php

namespace Drip\Payments\Utils;

use \GuzzleHttp\Exception\RequestException;

class RequestService
{
    const BASE_URI_SANDBOX = 'https://sbx-drip-be.usedrip.com.br/api/';
    const BASE_URI_PRODUCTION = 'https://drip-be.usedrip.com.br/api/';

    const CHECKOUTS_PATH = 'v1/checkouts';
    const IS_DISABLED_PATH = self::CHECKOUTS_PATH . '/disabled';
    const SIMULATOR_PATH = 'v1/instalments_simulator';
    const MERCHANT_CNPJ = 'v1/merchants/get_cnpj';
    const ERROR_LOGGER = 'v1/merchants/log_plugin_error';

    private static function options($testMode): array
    {
        return [
            'headers' => ['Content-Type' => 'application/json'],
            'base_uri' => $testMode
                ? self::BASE_URI_SANDBOX
                : self::BASE_URI_PRODUCTION,
            'connect_timeout' => 5,
            'read_timeout' => 5,
            'timeout' => 5,
            'verify' => false
        ];
    }

    public static function checkActiveAndConfigValues($configs)
    {
        $isActive = $configs['active'];
        if (!$isActive) {
            return false;
        }
        $isSandbox = $configs['is_sandbox'];

        $apiKey = $isSandbox == 0 ? $configs['api_key'] : $configs['sandbox_api_key'];

        if (strlen($apiKey) > 5) {
            return true;
        }

        return false;
    }

    public static function createInstance($configs)
    {
        $isSandbox = $configs['is_sandbox'];

        $apiKey = $isSandbox == 0 ? $configs['api_key'] : $configs['sandbox_api_key'];

        return new RequestService($apiKey, $isSandbox, '0.1.3', null);
    }

    public function __construct($merchantKey, $testMode, $plugin_version, \GuzzleHttp\Client $client = null)
    {
        $this->merchantKey = $merchantKey;
        $this->client = $client
            ? new \GuzzleHttp\Client(array_merge($client->getConfig(), self::options($testMode)))
            : new \GuzzleHttp\Client(self::options($testMode));
        $this->plugin_version = $plugin_version;
    }
    //$this->curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
    //$this->curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);

    public function isDisabled(): bool
    {
        try {
            $response = $this->client->get(self::IS_DISABLED_PATH);

            return json_decode($response->getBody())->isDisabled == true;
        } catch (RuntimeException $e) {
            $this->logError(json_encode([
                'url' => self::IS_DISABLED_PATH,
                'error' => $e->getMessage()
            ]));
            return true;
        }
    }

    public function createCheckout($data)
    {
        try {
            return $this->client->post(self::CHECKOUTS_PATH, [
                'json' => $data,
                'headers' => ['X-API-Key' => $this->merchantKey]
            ]);
        } catch (RequestException $e) {
            $this->logError(json_encode([
                'url' => self::CHECKOUTS_PATH,
                'error' => $e->getResponse()->getBody()
            ]));
            return $e->getResponse();
        } catch (RuntimeException $e) {
            $this->logError(json_encode([
                'url' => self::CHECKOUTS_PATH,
                'error' => $e->getMessage()
            ]));
            return NULL;
        }
    }

    public function getCheckout($checkoutId)
    {
        try {
            $response = $this->client->get(self::CHECKOUTS_PATH . '/' . $checkoutId, ['headers' => ['X-API-Key' => $this->merchantKey]]);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            return json_decode($response->getBody());
        } catch (RuntimeException $e) {
            $this->logError(json_encode([
                'url' => self::CHECKOUTS_PATH . '/' . $checkoutId,
                'error' => $e->getMessage()
            ]));
            return false;
        }
    }

    public function getCashback()
    {
        try {
            $response = $this->client->get(self::SIMULATOR_PATH . '?amount=99&date=2021-10-10', ['headers' => ['X-API-Key' => $this->merchantKey]]);
            if ($response->getStatusCode() !== 200) {
                return '2';
            }

            $resp_body = (array) json_decode($response->getBody());
            return $resp_body['cashbackRate'] * 100;
        } catch (RuntimeException $e) {
            $this->logError(json_encode([
                'url' => self::SIMULATOR_PATH,
                'error' => $e->getMessage()
            ]));
            return '2';
        }
    }

    public function getCnpj()
    {
        try {
            $response = $this->client->get(self::MERCHANT_CNPJ, ['headers' => ['X-API-Key' => $this->merchantKey]]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $resp_body = (array) json_decode($response->getBody());
            return $resp_body['cnpj'];
        } catch (RuntimeException $e) {
            $this->logError(json_encode([
                'url' => self::MERCHANT_CNPJ,
                'error' => $e->getMessage()
            ]));
            return null;
        }
    }

    private function logError($error)
    {
        try {
            $this->client->post(self::ERROR_LOGGER, [
                'json' => [
                    'website' => get_bloginfo('wpurl'),
                    'ecommerceType' => 'MAGENTO',
                    'pluginVersion' => $this->plugin_version,
                    'error' => $error
                ],
                'headers' => ['X-API-Key' => $this->merchantKey]
            ]);
            sleep(3);
        } catch (RuntimeException $e) {
            return null;
        }
    }
}
