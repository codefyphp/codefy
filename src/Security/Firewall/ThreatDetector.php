<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use JsonException;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Data\TypeException;

use function array_filter;
use function array_values;
use function is_array;
use function is_object;
use function is_scalar;
use function json_encode;
use function mb_substr;
use function preg_match;

final readonly class ThreatDetector
{
    public function __construct(
        private ThreatPatternRegistry $registry,
        private FirewallExclusionPolicy $exclusionPolicy,
        private ThreatLogger $threatLogger,
    ) {
    }

    /**
     * @throws TypeException
     * @throws JsonException
     */
    public function detect(ServerRequestInterface $request): ?ThreatMatch
    {
        $inputs = $this->extractInputs($request);

        foreach ($this->registry->all() as $pattern) {
            foreach ($inputs as $input) {
                if ($input->value === '') {
                    continue;
                }

                /*
                 * A threat rule must only inspect request sources for which
                 * that rule is meaningful.
                 */
                if (! $pattern->supports($input)) {
                    continue;
                }

                $result = preg_match($pattern->regex, $input->value);

                if ($result !== 1) {
                    continue;
                }

                $match = new ThreatMatch(
                    type: $pattern->type,
                    severity: $pattern->severity,
                    confidence: $pattern->confidence,
                    pattern: $pattern->regex,
                    value: mb_substr($input->value, 0, 1000),
                    group: $pattern->group,
                    source: $input->source,
                    field: $input->name,
                );

                if (
                        $this->exclusionPolicy->excludes(
                            request: $request,
                            pattern: $pattern,
                            input: $input
                        )
                ) {
                    if (
                            $this->exclusionPolicy->shouldLogExcludedMatch(
                                request: $request,
                                pattern: $pattern,
                                input: $input
                            )
                    ) {
                        $this->threatLogger->log(
                            request: $request,
                            match: new ThreatMatch(
                                type: $match->type,
                                severity: $match->severity,
                                confidence: $match->confidence,
                                pattern: $match->pattern,
                                value: $match->value,
                                group: $match->group,
                                source: $match->source,
                                field: $match->field,
                                excluded: true,
                            ),
                        );
                    }

                    continue;
                }

                return $match;
            }
        }

        return null;
    }

    /**
     * @return list<ThreatInput>
     * @throws JsonException
     */
    private function extractInputs(
        ServerRequestInterface $request
    ): array {
        $inputs = [
            new ThreatInput(
                source: 'method',
                name: 'method',
                value: $request->getMethod(),
            ),
            new ThreatInput(
                source: 'path',
                name: 'path',
                value: $request->getUri()->getPath(),
            ),
        ];

        $parsedBody = $request->getParsedBody();

        if (is_array($parsedBody) || is_object($parsedBody)) {
            $inputs = [
                ...$inputs,
                ...$this->flatten(
                    values: (array) $parsedBody,
                    source: 'body'
                ),
            ];
        }

        $queryParams = $request->getQueryParams();

        if ($queryParams !== []) {
            $inputs = [
                ...$inputs,
                ...$this->flatten(
                    values: $queryParams,
                    source: 'query'
                ),
            ];
        } elseif ($request->getUri()->getQuery() !== '') {
            $inputs[] = new ThreatInput(
                source: 'query',
                name: 'query_string',
                value: $request->getUri()->getQuery(),
            );
        }

        $inputs[] = new ThreatInput(
            source: 'uri',
            name: 'uri',
            value: (string) $request->getUri(),
        );

        foreach ($request->getHeaders() as $name => $values) {
            $inputs[] = new ThreatInput(source: 'header', name: strtolower($name), value: implode(', ', $values));
        }
        $inputs = [...$inputs, ...$this->flatten($request->getCookieParams(), 'cookie')];

        return array_values(
            array_filter(
                $inputs,
                static fn (ThreatInput $input): bool => $input->value !== ''
            )
        );
    }

    /**
     * @param array<string|int, mixed> $values
     * @return list<ThreatInput>
     * @throws JsonException
     */
    private function flatten(array $values, string $source, string $prefix = ''): array
    {
        $inputs = [];

        foreach ($values as $key => $value) {
            $name = $prefix === ''
            ? (string) $key
            : $prefix . '.' . $key;

            if (is_array($value)) {
                $inputs = [
                    ...$inputs,
                    ...$this->flatten(
                        values: $value,
                        source: $source,
                        prefix: $name
                    ),
                ];

                continue;
            }

            if (is_object($value)) {
                $inputs = [
                    ...$inputs,
                    ...$this->flatten(
                        values: (array) $value,
                        source: $source,
                        prefix: $name
                    ),
                ];

                continue;
            }

            if ($value === null) {
                continue;
            }

            if (is_scalar($value)) {
                $inputs[] = new ThreatInput(
                    source: $source,
                    name: $name,
                    value: (string) $value,
                );

                continue;
            }

            $inputs[] = new ThreatInput(
                source: $source,
                name: $name,
                value: json_encode($value, JSON_THROW_ON_ERROR),
            );
        }

        return $inputs;
    }
}
