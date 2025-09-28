<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Swoole;

use Codefy\Framework\Application;
use Exception;
use Qubus\Http\Swoole\Factory\RequestFactory;
use Qubus\Http\Swoole\ResponseMerger;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class BridgeManager
{
    private ?Application $app = null;

    private ?ResponseMerger $responseMerger = null;

    private RequestFactory $requestFactory;

    /**
     * @param Application $app
     * @param ResponseMerger $responseMerger
     * @param RequestFactory $requestFactory
     */
    public function __construct(
        Application $app,
        ResponseMerger $responseMerger,
        RequestFactory $requestFactory
    ) {
        $this->app = $app;
        $this->responseMerger = $responseMerger;
        $this->requestFactory = $requestFactory;
    }

    /**
     * @param Request $swooleRequest
     * @param Response $swooleResponse
     *
     * @return Response
     * @throws Exception
     */
    public function process(
        Request $swooleRequest,
        Response $swooleResponse,
    ): Response {

        $psrRequest = $this->requestFactory->createServerRequest($swooleRequest);

        if (!$psrRequest->getUri()->getHost() && isset($swooleRequest->header['host'])) {
            $uri = $psrRequest->getUri()->withHost($swooleRequest->header['host']);
            $psrRequest = $psrRequest->withUri($uri);
        }

        try {
            $response = $this->app->handle($psrRequest);

            return $this->responseMerger->toSwoole($response, $swooleResponse);
        } catch (Exception $e) {
            $swooleResponse->status(500);
            $swooleResponse->end("BridgeManager error: {$e->getMessage()}");
            return $swooleResponse;
        }
    }
}
