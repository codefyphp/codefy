<?php

declare(strict_types=1);

use Codefy\Framework\Http\Middleware\FirewallMiddleware;
use Codefy\Framework\Security\Firewall\BlockedResponseFactory;
use Codefy\Framework\Security\Firewall\FirewallExclusionPolicy;
use Codefy\Framework\Security\Firewall\ThreatDetector;
use Codefy\Framework\Security\Firewall\ThreatMatch;
use Codefy\Framework\Security\Firewall\ThreatPatternRegistry;
use Codefy\Framework\Tests\Security\Fixtures\FakeRequestHandler;
use Codefy\Framework\Tests\Security\Fixtures\FakeThreatLogger;
use Laminas\Diactoros\Response;

function middleware_threat_match(
    string $severity = 'high'
): ThreatMatch {
    return new ThreatMatch(
        type: 'file_traversal',
        severity: $severity,
        confidence: 95.0,
        pattern: '#\.\.[/\\\\]#',
        value: '../../etc/passwd',
        group: 'file_traversal',
        source: 'query',
        field: 'file'
    );
}

it('passes through when the firewall is disabled', function (): void {
    $config = firewall_config([
        'enabled' => false,
    ]);

    $logger = new FakeThreatLogger();
    $detector = new ThreatDetector(
        new ThreatPatternRegistry(codefy()->configContainer),
        new FirewallExclusionPolicy(codefy()->configContainer),
        $logger,
    );

    $middleware = new FirewallMiddleware(
        detector: $detector,
        logger: $logger,
        blockedResponseFactory: new BlockedResponseFactory(),
        config: $config
    );

    $expectedResponse = new Response(status: 200);
    $handler = new FakeRequestHandler($expectedResponse);
    $request = firewall_request('GET', '/dashboard');

    $response = $middleware->process($request, $handler);

    expect($response)
        ->toBe($expectedResponse)
        ->and($handler->calls)->toBe(1)
        ->and($logger->calls)->toBe(0);
});

it('passes ignored paths through without running detection', function (): void {
    $config = firewall_config([
        'enabled' => true,
        'ignored_paths' => [
            '/health',
        ],
    ]);

    $logger = new FakeThreatLogger();
    $detector = new ThreatDetector(
        new ThreatPatternRegistry(codefy()->configContainer),
        new FirewallExclusionPolicy(codefy()->configContainer),
        $logger
    );

    $middleware = new FirewallMiddleware(
        detector: $detector,
        logger: $logger,
        blockedResponseFactory: new BlockedResponseFactory(),
        config: $config
    );

    $expectedResponse = new Response(status: 200);
    $handler = new FakeRequestHandler($expectedResponse);
    $request = firewall_request('GET', '/health/database');

    $response = $middleware->process($request, $handler);

    expect($response)
        ->toBe($expectedResponse)
        ->and($handler->calls)->toBe(1)
        ->and($logger->calls)->toBe(0);
});

it('passes through when no threat is detected', function (): void {
    $config = firewall_config([
        'enabled' => true,
    ]);

    $logger = new FakeThreatLogger();
    $detector = new ThreatDetector(
        new ThreatPatternRegistry(codefy()->configContainer),
        new FirewallExclusionPolicy(codefy()->configContainer),
        $logger
    );

    $middleware = new FirewallMiddleware(
        detector: $detector,
        logger: $logger,
        blockedResponseFactory: new BlockedResponseFactory(),
        config: $config
    );

    $expectedResponse = new Response(status: 200);
    $handler = new FakeRequestHandler($expectedResponse);
    $request = firewall_request('GET', '/articles');

    $response = $middleware->process($request, $handler);

    expect($response)
        ->toBe($expectedResponse)
        ->and($handler->calls)->toBe(1)
        ->and($logger->calls)->toBe(0);
});
