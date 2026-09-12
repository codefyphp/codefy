<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware;

use Codefy\Framework\Security\Firewall\BlockedResponseFactory;
use Codefy\Framework\Security\Firewall\ThreatDetector;
use Codefy\Framework\Security\Firewall\ThreatLogger;
use Codefy\Framework\Security\Firewall\ThreatMatch;
use Codefy\Framework\Security\Firewall\ThreatNotifier;
use Codefy\Framework\Security\Firewall\ThreatNotifierCollection;
use Exception;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use ReflectionClass;
use Throwable;

use function array_any;
use function Codefy\Framework\Helpers\logger;
use function is_string;
use function ltrim;
use function preg_replace;
use function rtrim;
use function spl_object_id;
use function str_ends_with;
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
        private ?ThreatNotifierCollection $notifiers = null,
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

                return $path === $ignoredPath
                    || str_starts_with(
                        haystack: $path,
                        needle: $ignoredPath . '/'
                    );
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
    private function notify(
        ServerRequestInterface $request,
        ThreatMatch $match
    ): void {
        foreach ($this->notifiers() as $notifier) {
            if (! $this->isNotifierEnabled($notifier)) {
                continue;
            }

            try {
                /*
                 * Positional arguments are intentional. Implementations of
                 * ThreatNotifier are not required to use the parameter names
                 * $request and $match.
                 */
                $notifier->notify($request, $match);
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

    /**
     * Merge dependency-injected notifiers with legacy notifier objects stored
     * directly in firewall.notifiers.
     *
     * Associative configuration entries such as:
     *
     * email:
     *     enabled: true
     *
     * are not notifier instances and are ignored here.
     *
     * @return list<ThreatNotifier>
     *
     * @throws TypeException
     */
    private function notifiers(): array
    {
        /** @var array<int, ThreatNotifier> $notifiers */
        $notifiers = [];

        /** @var array<int, true> $registered */
        $registered = [];

        if ($this->notifiers !== null) {
            foreach ($this->notifiers->all() as $notifier) {
                $this->addNotifier(
                    notifiers: $notifiers,
                    registered: $registered,
                    notifier: $notifier
                );
            }
        }

        $legacyNotifiers = $this->config->array(
            key: 'firewall.notifiers',
            default: []
        );

        foreach ($legacyNotifiers as $notifier) {
            if (! $notifier instanceof ThreatNotifier) {
                continue;
            }

            $this->addNotifier(
                notifiers: $notifiers,
                registered: $registered,
                notifier: $notifier
            );
        }

        return $notifiers;
    }

    /**
     * @param array<int, ThreatNotifier> $notifiers
     * @param array<int, true> $registered
     */
    private function addNotifier(
        array &$notifiers,
        array &$registered,
        ThreatNotifier $notifier
    ): void {
        $objectId = spl_object_id($notifier);

        if (isset($registered[$objectId])) {
            return;
        }

        $registered[$objectId] = true;
        $notifiers[] = $notifier;
    }

    /**
     * Resolve the notifier configuration key from its class name.
     *
     * Examples:
     *
     * EmailThreatNotifier => email
     * SlackThreatNotifier => slack
     * MicrosoftTeamsThreatNotifier => microsoft_teams
     *
     * A notifier with no corresponding configuration entry remains enabled
     * for backwards compatibility.
     *
     * @throws TypeException
     */
    private function isNotifierEnabled(ThreatNotifier $notifier): bool
    {
        $name = $this->notifierName($notifier);

        if ($name === '') {
            return true;
        }

        $settings = $this->config->array(
            key: 'firewall.notifiers.' . $name,
            default: []
        );

        /*
         * No matching settings entry means this is either a legacy notifier
         * or a custom notifier. Keep it enabled for backwards compatibility.
         */
        if ($settings === []) {
            return true;
        }

        $enabled = $settings['enabled'] ?? true;

        return $enabled === true;
    }

    private function notifierName(ThreatNotifier $notifier): string
    {
        $shortName = new ReflectionClass($notifier)
            ->getShortName();

        if (
            str_ends_with(
                haystack: $shortName,
                needle: 'ThreatNotifier'
            )
        ) {
            $shortName = substr(
                string: $shortName,
                offset: 0,
                length: -14
            );
        } elseif (
            str_ends_with(
                haystack: $shortName,
                needle: 'Notifier'
            )
        ) {
            $shortName = substr(
                string: $shortName,
                offset: 0,
                length: -8
            );
        }

        $name = preg_replace(
            pattern: '/(?<!^)[A-Z]/',
            replacement: '_$0',
            subject: $shortName
        );

        return strtolower($name ?? '');
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
