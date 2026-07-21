<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Security\Firewall\BlockedResponseFactory;
use Codefy\Framework\Security\Firewall\ThreatDetector;
use Codefy\Framework\Security\Firewall\ThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatMatch;
use Codefy\Framework\Security\Firewall\ThreatNotifier;
use Exception;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Throwable;

use function array_any;
use function Codefy\Framework\Helpers\logger;
use function is_string;
use function rtrim;
use function str_starts_with;
use function strtolower;

final readonly class FirewallMiddleware implements MiddlewareInterface
{
    private const array SEVERITY_RANK = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'critical' => 4,
    ];

    public function __construct(
        private ThreatDetector $detector,
        private ThreatLogger $logger,
        private BlockedResponseFactory $blockedResponseFactory,
        private ConfigContainer $config,
    ) {
    }

    /**
     * @throws TypeException
     * @throws JsonException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->isEnabled() || $this->isIgnored($request)) {
            return $handler->handle($request);
        }

        $match = $this->detector->detect($request);

        if ($match === null) {
            return $handler->handle($request);
        }

        $this->logger->log(
            request: $request,
            match: $match
        );

        if ($this->shouldAlert($match)) {
            $this->notify(
                request: $request,
                match: $match
            );
        }

        if (! $this->shouldBlock()) {
            return $handler->handle($request);
        }

        return $this->blockedResponseFactory->create(
            match: $match
        );
    }

    /**
     * @throws TypeException
     */
    private function isEnabled(): bool
    {
        return $this->config->boolean(
            key: 'firewall.enabled',
            default: false
        );
    }

    /**
     * @throws TypeException
     */
    private function shouldBlock(): bool
    {
        return $this->config->boolean(
            key: 'firewall.block',
            default: true
        );
    }

    /**
     * @throws TypeException
     */
    private function isIgnored(ServerRequestInterface $request): bool
    {
        $path = $this->normalizePath(
            $request->getUri()->getPath()
        );

        $ignoredPaths = $this->config->array(
            key: 'firewall.ignored_paths',
            default: []
        );

        return array_any(
            $ignoredPaths,
            function (mixed $ignoredPath) use ($path): bool {
                if (
                        ! is_string($ignoredPath)
                        || $ignoredPath === ''
                ) {
                    return false;
                }

                $ignoredPath = $this->normalizePath(
                    $ignoredPath
                );

                if ($ignoredPath === '/') {
                    return $path === '/';
                }

                return $path === $ignoredPath || str_starts_with($path, $ignoredPath . '/');
            }
        );
    }

    /**
     * @throws TypeException
     */
    private function shouldAlert(ThreatMatch $match): bool
    {
        if ($match->excluded) {
            return false;
        }

        $minimumSeverity = strtolower(
            $this->config->string(
                key: 'firewall.alert_min_severity',
                default: 'high'
            )
        );

        $minimumRank = self::SEVERITY_RANK[$minimumSeverity] ?? self::SEVERITY_RANK['high'];
        $matchRank = self::SEVERITY_RANK[strtolower($match->severity)] ?? 0;

        return $matchRank >= $minimumRank;
    }

    /**
     * @throws TypeException
     */
    private function notify(ServerRequestInterface $request, ThreatMatch $match): void
    {
        $notifiers = $this->config->array(
            key: 'firewall.notifiers',
            default: []
        );

        foreach ($notifiers as $notifier) {
            if (! $notifier instanceof ThreatNotifier) {
                continue;
            }

            try {
                $notifier->notify(
                    request: $request,
                    match: $match
                );
            } catch (Throwable $exception) {
                $this->logNotifierFailure(
                    request: $request,
                    match: $match,
                    notifier: $notifier,
                    exception: $exception
                );
            }
        }
    }

    private function normalizePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . ltrim(
            rtrim($path, '/'),
            '/'
        );
    }

    private function logNotifierFailure(
        ServerRequestInterface $request,
        ThreatMatch $match,
        ThreatNotifier $notifier,
        Throwable $exception
    ): void {
        logger(
            level: 'error',
            message: 'Firewall threat notifier failed.',
            context: [
                'exception' => $exception,
                'notifier' => $notifier::class,
                'threat_group' => $match->group,
                'threat_type' => $match->type,
                'request_path' => $request->getUri()->getPath(),
            ]
        );
    }
}
