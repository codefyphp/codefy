<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use Psr\Http\Message\ServerRequestInterface;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;

use function array_map;
use function in_array;
use function is_array;
use function is_string;
use function rtrim;
use function strtoupper;

final readonly class FirewallExclusionPolicy
{
    public function __construct(
        private ConfigContainer $config,
    ) {
    }

    /**
     * Returns true when the matching firewall rule should be excluded.
     *
     * @throws TypeException
     */
    public function excludes(
        ServerRequestInterface $request,
        ThreatPattern $pattern,
        ThreatInput $input
    ): bool {
        foreach ($this->exclusions() as $exclusion) {
            if (! $this->pathMatches($request, $exclusion)) {
                continue;
            }

            if (! $this->methodMatches($request, $exclusion)) {
                continue;
            }

            if (! $this->ruleMatches($pattern, $exclusion)) {
                continue;
            }

            if (! $this->sourceMatches($input, $exclusion)) {
                continue;
            }

            if (! $this->fieldMatches($input, $exclusion)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Whether excluded matches should still be logged.
     *
     * @throws TypeException
     */
    public function shouldLogExcludedMatch(
        ServerRequestInterface $request,
        ThreatPattern $pattern,
        ThreatInput $input
    ): bool {
        foreach ($this->exclusions() as $exclusion) {
            if (
                    $this->pathMatches($request, $exclusion)
                    && $this->methodMatches($request, $exclusion)
                    && $this->ruleMatches($pattern, $exclusion)
                    && $this->sourceMatches($input, $exclusion)
                    && $this->fieldMatches($input, $exclusion)
            ) {
                return $exclusion->log;
            }
        }

        return false;
    }

    /**
     * @return list<FirewallExclusion>
     * @throws TypeException
     */
    private function exclusions(): array
    {
        $configuredExclusions = $this->config->array(
            key: 'firewall.exclusions',
            default: []
        );

        $exclusions = [];

        foreach ($configuredExclusions as $configuredExclusion) {
            if (! is_array($configuredExclusion)) {
                continue;
            }

            $path = $configuredExclusion['path'] ?? null;

            if (! is_string($path) || $path === '') {
                continue;
            }

            $exclusions[] = new FirewallExclusion(
                path: $path,
                methods: $this->stringList($configuredExclusion['methods'] ?? []),
                rules: $this->stringList($configuredExclusion['rules'] ?? []),
                sources: $this->stringList($configuredExclusion['sources'] ?? []),
                fields: $this->stringList($configuredExclusion['fields'] ?? []),
                log: (bool) ($configuredExclusion['log'] ?? true),
            );
        }

        return $exclusions;
    }

    private function pathMatches(
        ServerRequestInterface $request,
        FirewallExclusion $exclusion
    ): bool {
        $requestPath = rtrim($request->getUri()->getPath(), '/');
        $excludedPath = rtrim($exclusion->path, '/');

        if ($requestPath === '') {
            $requestPath = '/';
        }

        if ($excludedPath === '') {
            $excludedPath = '/';
        }

        if (str_ends_with($excludedPath, '*')) {
            $prefix = rtrim(substr($excludedPath, 0, -1), '/');

            return $requestPath === $prefix
            || str_starts_with($requestPath, $prefix . '/');
        }

        return $requestPath === $excludedPath;
    }

    private function methodMatches(
        ServerRequestInterface $request,
        FirewallExclusion $exclusion
    ): bool {
        if ($exclusion->methods === []) {
            return true;
        }

        $methods = array_map(
            static fn (string $method): string => strtoupper($method),
            $exclusion->methods
        );

        return in_array(strtoupper($request->getMethod()), $methods, true);
    }

    private function ruleMatches(
        ThreatPattern $pattern,
        FirewallExclusion $exclusion
    ): bool {
        return $exclusion->rules === []
        || in_array('*', $exclusion->rules, true)
        || in_array($pattern->group, $exclusion->rules, true)
        || in_array($pattern->type, $exclusion->rules, true);
    }

    private function sourceMatches(
        ThreatInput $input,
        FirewallExclusion $exclusion
    ): bool {
        return $exclusion->sources === []
        || in_array($input->source, $exclusion->sources, true)
        || in_array('*', $exclusion->sources, true);
    }

    private function fieldMatches(
        ThreatInput $input,
        FirewallExclusion $exclusion
    ): bool {
        if ($exclusion->fields === []) {
            return true;
        }

        foreach ($exclusion->fields as $field) {
            if ($field === '*' || $input->name === $field) {
                return true;
            }

            if (
                    str_ends_with($field, '.*')
                    && str_starts_with(
                        $input->name,
                        substr($field, 0, -1)
                    )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(
            array_filter(
                $value,
                static fn (mixed $item): bool => is_string($item) && $item !== ''
            )
        );
    }
}
