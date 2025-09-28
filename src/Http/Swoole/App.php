<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Swoole;

use Codefy\Framework\Application;
use Qubus\Http\Factories\Psr17Factory;
use Qubus\Http\Swoole\Factory\RequestFactory;
use Qubus\Http\Swoole\ResponseMerger;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;

use function flush;
use function time;

final class App
{
    //phpcs:disable
    public function __construct(
        public private(set) ?Application $app = null {
            get {
                return $this->app;
            }
        },
        public private(set) ?Server $server = null {
            get {
                return $this->server ?? $this->app->make(name: Server::class);
            }
        },
        public int $serverStartTimestamp = 0 {
            get {
                if ($this->serverStartTimestamp === 0) {
                    return 0;
                }
                return time() - $this->serverStartTimestamp;
            }
        }
    ) {
        $this->app->alias(original: App::class, alias: self::class);
    }
    //phpcs:enable

    public function init(callable $routesCallable): void
    {
        $this->initRoutes(callable: $routesCallable);

        $psr17 = new Psr17Factory();
        $bridge = new BridgeManager(
            app: $this->app,
            responseMerger: new ResponseMerger(),
            requestFactory: new RequestFactory(uriFactory: $psr17, streamFactory: $psr17, uploadedFileFactory: $psr17)
        );

        $this->server->on(
            event_name: 'request',
            callback: function (SwooleRequest $request, SwooleResponse $response) use ($bridge) {

                try {
                    $response->header(key: 'X-Powered-By', value: 'Swoole + CodefyPHP');

                    // Boot fresh per request, ensures correct routing
                    $this->app->boot();

                    $bridge->process(swooleRequest: $request, swooleResponse: $response)->end();

                    flush();
                } catch (\Throwable $e) {
                    $response->status(http_code: 500);
                    $response->end(content: "Internal Server Error: {$e->getMessage()}");
                }
            }
        );
    }

    /**
     * Starts Swoole Server
     */
    public function start(): void
    {
        $this->serverStartTimestamp = time();
        $this->server->start();
    }

    private function initRoutes(callable $callable): void
    {
        $callable($this->app);
    }
}
