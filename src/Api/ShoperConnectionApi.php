<?php

namespace App\Api;

use App\Service\LoggerService;
use App\Service\ShoperConnectionSettingsService;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ShoperConnectionApi
{

    private HttpClientInterface $client;
    private CacheInterface $cache;
    private string $token;
    private mixed $tokenExpiry;
    private LoggerService $logger;

    protected const SHOPER_TOKEN_CACHE_NAME = 'shoper_api_token';
    private ShoperConnectionSettingsService $shoperConnectionSettingsService;

    public function __construct(
        HttpClientInterface $client,
        ShoperConnectionSettingsService $shoperConnectionSettingsService,
        CacheInterface $cache,
        LoggerService $loggerService
    ) {
        $this->client = $client;
        $this->cache = $cache;
        $this->shoperConnectionSettingsService = $shoperConnectionSettingsService;
        $this->logger = $loggerService;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function authenticate(): void
    {
        $settings = $this->shoperConnectionSettingsService->getShoperConnectionSettings();
        $username = $settings->getRestUser();
        $password = $settings->getRestPassword();
        $url = $this->createApiUrl('auth');

        $authHeader = 'Basic ' . base64_encode("$username:$password");

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Authorization' => $authHeader,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            $this->logger->getApiLogger()->error('Failed to connect to Shoper API',[
                'url' => $url,
                'response' => $response->getStatusCode(),
                'content' => $response->getContent(),
            ]);
            throw new \Exception('Failed to connect to Shoper API: ' . $response->getContent());
        }

        try {
            $responseContent = $response->toArray();
            $this->token = $responseContent['access_token'];
            $expiresIn = $responseContent['expires_in'] ?? 3600;
            if (!is_numeric($expiresIn) || $expiresIn <= 0) {
                $this->logger->getApiLogger()->error('Invalid expires_in value from Shoper API',[
                    'expiresIn' => $expiresIn,
                ]);
                throw new \Exception('Invalid expires_in value from Shoper API.');
            }
            $this->tokenExpiry = (new \DateTime())->add(new \DateInterval("PT{$expiresIn}S"));
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->logger->getApiLogger()->error('Failed to connect to Shoper API',[
                'url' => $url,
                'response' => $response->getStatusCode(),
                'content' => $response->getContent(),
            ]);
        }
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws \Exception
     */
    public function makeApiRequest(string $endpoint, string $method = 'GET', array $options = [], array $jsonBody = []): array
    {
        $url = $this->createApiUrl($endpoint);

        $maxRetries = 5;
        $retry = 0;
        $delay = 1;
        $tokenRefreshed = false;

        while ($retry <= $maxRetries) {
            try {
                $token = $this->getToken();

                $response = $this->client->request($method, $url, array_merge($options, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                    ],
                    'json' => $jsonBody,
                ]));

                $statusCode = $response->getStatusCode();

                if ($statusCode === 401 && !$tokenRefreshed) {
                    $this->logger->getApiLogger()->warning('Unauthorized. Refreshing token...');
                    $this->deleteToken();
                    $tokenRefreshed = true;
                    continue;
                }

                if ($statusCode === 429) {
                    $retryAfter = (int) $response->getHeaders(false)['retry-after'][0] ?? $delay;
                    sleep($retryAfter);
                    $retry++;
                    $delay *= 2;
                    continue;
                }

                if ($statusCode !== 200) {
                    $this->logger->getApiLogger()->error('API request failed', [
                        'code' => $statusCode,
                        'url' => $url,
                        'content' => $response->getContent(false),
                    ]);
                    throw new \Exception('API request failed: ' . $response->getContent(false));
                }

                $headers = $response->getHeaders(false);
                $calls = (int) ($headers['x-shop-api-calls'][0] ?? 0);
                $limit = (int) ($headers['x-shop-api-limit'][0] ?? 10);

                if ($calls >= $limit - 1) {
                    sleep(1);
                }

                $content = $response->getContent(false);
                $decoded = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return ['raw' => $content];
                }

                return is_array($decoded) ? $decoded : ['value' => $decoded];

            } catch (TransportExceptionInterface | ClientExceptionInterface $e) {
                $this->logger->getApiLogger()->error('API request error', [
                    'code' => $e->getCode(),
                    'url' => $url,
                    'content' => $e->getMessage(),
                ]);

                if ($retry < $maxRetries) {
                    sleep($delay);
                    $retry++;
                    $delay *= 2;
                    continue;
                }

                throw new \Exception('API request error: ' . $url . ' [' . $method . ']', $e->getCode(), $e);
            }
        }

        throw new \Exception('API request failed after max retries: ' . $url);
    }

    public function createApiUrl($endpoint): string
    {
        $baseUrl = $this->shoperConnectionSettingsService->getShoperConnectionSettings()->getShopUrl();
        return $baseUrl . 'webapi/rest/' . $endpoint;
    }

    public function getToken(): string
    {
        $cacheItem = $this->cache->getItem(self::SHOPER_TOKEN_CACHE_NAME);
        $expiry = $cacheItem->getMetadata()['expiry'] ?? null;

        if (!$cacheItem->isHit() || ($expiry && $expiry <= time())) {
            $this->logger->getApiLogger()->info('Token expired or missing. Refreshing...');

            $this->deleteToken();

            return $this->cache->get(self::SHOPER_TOKEN_CACHE_NAME, function (ItemInterface $item) {
                $this->authenticate();

                $ttl = max(0, $this->tokenExpiry->getTimestamp() - time() - 60);
                $item->expiresAfter($ttl);

                return $this->token;
            });
        }

        return $cacheItem->get();
    }


    public function getTokenExpiry()
    {
        $cacheItem = $this->cache->getItem(self::SHOPER_TOKEN_CACHE_NAME);
        if (!$cacheItem->isHit()) {
            return null;
        }

        return $cacheItem->getMetadata()['expiry'] ?? null;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function fetchAllPages(string $endpoint, string $method = 'GET', array $options = [])
    {
        $allData = [];
        $pages = 1;

        $data = $this->makeApiRequest($endpoint, $method, $options);

        if ($data) {
            $allData = $this->getDataFromResponse($data);

            if (isset($data['pages'])) {
                $pages = (int) $data['pages'];
            }
            if ($pages > 1) {
                for ($i = 2; $i <= $pages; $i++) {
                    $options['query']['page'] = $i;
                    $data = $this->makeApiRequest($endpoint, $method, $options);

                    if ($data) {
                        $allData = array_merge($allData, $this->getDataFromResponse($data));
                    } else {
                        throw new \Exception('Unexpected response format on page ' . $i);
                    }
                }
            }
        } else {
            throw new \Exception('Unexpected initial response format');
        }

        return $allData;
    }


    public function getDataFromResponse($response,$key='list')
    {
        if(isset($response[$key])) {
            return $response[$key];
        }
        return [];
    }

    public function isConnected(): bool
    {
        try {
            $version = $this->getDataFromResponse(
                $this->makeApiRequest('application-version'),
                'version'
            );

            return !empty($version);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function deleteToken()
    {
        $this->cache->deleteItem(self::SHOPER_TOKEN_CACHE_NAME);
    }
}