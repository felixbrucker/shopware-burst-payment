<?php declare(strict_types=1);

namespace Burst\BurstPayment\BurstRate;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class CoinGeckoApi
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client = new Client([
            'base_uri' => 'https://api.coingecko.com/api/v3/',
            'timeout'  => 60, // In seconds
        ]);
    }

    /**
     * @return array
     * @throws GuzzleException
     */
    public function getSupportedCurrencies(): array
    {
        return $this->doApiCall('GET', 'simple/supported_vs_currencies');
    }

    /**
     * @param string $symbol
     * @param string $currency
     * @return float
     * @throws GuzzleException
     */
    public function getRate(string $symbol, string $currency): float
    {
        $prices = $this->getRates([$symbol], [$currency]);

        return $prices[strtolower($symbol)][strtolower($currency)];
    }

    /**
     * @param array $symbols
     * @param array $currencies
     * @return array
     * @throws GuzzleException
     */
    public function getRates(array $symbols, array $currencies): array
    {
        return $this->doApiCall('GET', 'simple/price', [
            'vs_currencies' => implode(',', $currencies),
            'ids' => implode(',', $symbols),
        ]);
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return mixed
     * @throws RequestException
     * @throws GuzzleException
     */
    private function doApiCall(string $method, string $endpoint, array $params = [])
    {
        try {
            $response = $this->client->request($method, $endpoint, [
                'query' => $params,
            ]);

            return json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            $request = $e->getRequest();
            $response = $e->getResponse();
            $this->logger->error('CoinGecko API | Error: ' . $e->getMessage(), [
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getUri(),
                    'headers' => $request->getHeaders(),
                ],
                'response' => [
                    'statusCode' => $response->getStatusCode(),
                    'body' => $response->getBody(),
                    'headers' => $response->getHeaders(),
                ],
                'stackTrace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
