<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Security\Firewall\BlockedResponseFactory;
use Codefy\Framework\Security\Firewall\ThreatDetector;
use Codefy\Framework\Security\Firewall\ThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatMatch;
use Exception;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;

final readonly class FirewallMiddleware implements MiddlewareInterface
{
    /**
     * @param ThreatDetector $detector
     * @param ThreatLogger $logger
     * @param BlockedResponseFactory $blockedResponseFactory
     * @param ConfigContainer $config
     */
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
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (! $this->config->boolean('firewall.enabled') || $this->isIgnored($request)) {
            return $handler->handle($request);
        }

        $match = $this->detector->detect($request);

        if ($match === null) {
            return $handler->handle($request);
        }

        $this->logger->log($request, $match);

        if ($this->shouldAlert($match)) {
            foreach ($this->config->array(key: 'firewall.notifiers') as $notifier) {
                $notifier->notify($request, $match);
            }
        }

        if (! $this->config->boolean('firewall.block')) {
            return $handler->handle($request);
        }

        return $this->blockedResponseFactory->create($match);
    }

    /**
     * @throws TypeException
     */
    private function isIgnored(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        return array_any(
            $this->config->array('firewall.ignored_paths', []),
            fn($ignoredPath) => $path === $ignoredPath || str_starts_with($path, rtrim($ignoredPath, '/') . '/')
        );
    }

    /**
     * @throws TypeException
     */
    private function shouldAlert(ThreatMatch $match): bool
    {
        $rank = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
        ];

        return ($rank[$match->severity] ?? 0) >=
        ($rank[$this->config->string(key: 'firewall.alert_min_severity', default: 'high')]);
    }
}
