<?php declare(strict_types=1);

namespace Burst\BurstPayment\BurstApi;

use Burst\BurstPayment\Config\PluginConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BurstApiController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route(
     *     "/api/v{version}/_action/burst-payment/validate-wallet-connection",
     *     name="api.action.burst-payment.validate-wallet-connection",
     *     methods={"POST"}
     * )
     * @param Request $request
     * @return JsonResponse
     */
    public function validateWalletConnection(Request $request): JsonResponse
    {
        $requestBody = json_decode($request->getContent(), true);
        $burstApi = new BurstApi(
            new PluginConfig([
                'burstWalletUrl' => $requestBody['burstWalletUrl'],
            ]),
            $this->logger
        );

        return new JsonResponse(['isReachable' => $burstApi->isReachable()]);
    }
}
