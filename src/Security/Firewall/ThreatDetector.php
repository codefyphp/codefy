<?php

declare(strict_types=1);

namespace Codefy\Framework\Security\Firewall;

use JsonException;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Data\TypeException;

final readonly class ThreatDetector
{
    public function __construct(
        private ThreatPatternRegistry $registry,
    ) {
    }

    /**
     * @throws TypeException
     * @throws JsonException
     */
    public function detect(ServerRequestInterface $request): ?ThreatMatch
    {
        $values = $this->extractValues($request);

        foreach ($this->registry->all() as $pattern) {
            foreach ($values as $value) {
                if ($value === '') {
                    continue;
                }

                if (@preg_match($pattern->regex, $value) === 1) {
                    return new ThreatMatch(
                        type: $pattern->type,
                        severity: $pattern->severity,
                        confidence: $pattern->confidence,
                        pattern: $pattern->regex,
                        value: mb_substr($value, 0, 1000),
                    );
                }
            }
        }

        return null;
    }

    /**
     * @return list<string>
     * @throws JsonException
     */
    private function extractValues(ServerRequestInterface $request): array
    {
        $uri = (string) $request->getUri();

        $values = [
            $request->getMethod(),
            $uri,
            $request->getUri()->getPath(),
            $request->getUri()->getQuery(),
            $request->getHeaderLine('User-Agent'),
            $request->getHeaderLine('Referer'),
            $request->getHeaderLine('X-Forwarded-For'),
        ];

        $values[] = json_encode($request->getQueryParams(), JSON_THROW_ON_ERROR);

        $parsedBody = $request->getParsedBody();

        if (is_array($parsedBody) || is_object($parsedBody)) {
            $values[] = json_encode($parsedBody, JSON_THROW_ON_ERROR);
        }

        return array_values(array_filter($values, static fn ($value): bool => is_string($value)));
    }
}
