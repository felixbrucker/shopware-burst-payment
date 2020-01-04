<?php declare(strict_types=1);

namespace Burst\BurstPayment\BurstApi;

use DateTime;
use DateTimeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class BurstApi
{
    /**
     * @var BurstApiConfig
     */
    private $config;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        BurstApiConfig $burstApiConfig,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->config = $burstApiConfig;

        $this->client = new Client([
            'base_uri' => rtrim($this->config->walletUrl, '/') . '/burst',
            'timeout'  => 60, // In seconds
        ]);
    }

    /**
     * @return array
     * @throws BurstApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUnconfirmedTransactions(): array
    {
        $result = $this->doApiCall('GET', 'getUnconfirmedTransactions', [
            'account' => $this->config->burstAddress,
        ]);

        return array_values(array_filter($result['unconfirmedTransactions'], function ($transaction) {
            return $transaction['recipientRS'] === $this->config->burstAddress;
        }));
    }

    /**
     * @param DateTimeInterface $dateTime
     * @return array
     * @throws BurstApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactionsFrom(DateTimeInterface $dateTime): array
    {
        $burstTimeNow = $this->getTime();
        $diffRealTimeFromNow = (new DateTime('NOW'))->getTimestamp() - $dateTime->getTimestamp();
        $burstTimeToSearchFrom = $burstTimeNow - $diffRealTimeFromNow - 60;

        $allTransactions = [];
        $exit = false;
        $offset = 0;
        $limit = 50;
        while (!$exit) {
            $transactions = $this->getTransactions($offset, $limit, $burstTimeToSearchFrom);
            if (count($transactions) !== $limit) {
                $exit = true;
            }
            foreach ($transactions as $transaction) {
                $allTransactions[] = $transaction;
            }
            $offset += $limit;
        }

        return $allTransactions;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param int|null $timestamp
     * @return array
     * @throws BurstApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransactions(int $offset = 0, int $limit = 100, int $timestamp = null): array
    {
        $queryParams = [
            'account' => $this->config->burstAddress,
            'firstIndex' => $offset,
            'lastIndex' => $offset + $limit - 1, // Last index is included
        ];
        if ($timestamp) {
            $queryParams['timestamp'] = $timestamp;
        }
        $result = $this->doApiCall('GET', 'getAccountTransactions', $queryParams);

        return array_values(array_filter($result['transactions'], function ($transaction) {
            return $transaction['recipientRS'] === $this->config->burstAddress;
        }));
    }

    /**
     * @param string $transactionId
     * @return array
     * @throws BurstApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTransaction(string $transactionId): array
    {
        return $this->doApiCall('GET', 'getTransaction', [
            'transaction' => $transactionId,
        ]);
    }

    /**
     * @return mixed
     * @throws BurstApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getTime()
    {
        $result = $this->doApiCall('GET', 'getTime');

        return $result['time'];
    }

    /**
     * @param string $method
     * @param string $requestType
     * @param array $params
     * @return mixed
     * @throws BurstApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function doApiCall(string $method, string $requestType, array $params = [])
    {
        $queryParameters = array_merge($params, ['requestType' => $requestType]);

        try {
            $response = $this->client->request($method, '', [
                'query' => $queryParameters,
            ]);

            $result = json_decode((string) $response->getBody(), true);

            if (isset($result['errorCode']) || isset($result['errorDescription'])) {
                $this->logger->error('[Burst Payment] [Burst API] Error: ' . $result['errorDescription'], [
                    'request' => [
                        'method' => $method,
                        'uri' => $this->client->getConfig()['base_uri'],
                        'queryParameters' => $queryParameters,
                    ],
                    'response' => [
                        'statusCode' => $response->getStatusCode(),
                        'body' => $result,
                        'headers' => $response->getHeaders(),
                    ],
                ]);

                throw new BurstApiException($result, $result['errorDescription'], (int) $result['errorCode']);
            }

            return $result;
        } catch (RequestException $e) {
            $request = $e->getRequest();
            $response = $e->getResponse();
            $this->logger->error('[Burst Payment] [Burst API] Error: ' . $e->getMessage(), [
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
            ]);

            throw $e;
        }
    }
}
