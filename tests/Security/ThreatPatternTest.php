<?php

declare(strict_types=1);

namespace Codefy\Tests\Unit\Framework\Security\Firewall;

use Codefy\Framework\Security\Firewall\ThreatInput;
use Codefy\Framework\Security\Firewall\ThreatPattern;

it('supports every source when allowed sources are empty', function (): void {
    $pattern = new ThreatPattern(
        group: 'ssrf',
        type: 'ssrf',
        severity: 'high',
        confidence: 88.0,
        regex: '/localhost/i',
    );

    $input = new ThreatInput(
        source: 'header',
        name: 'User-Agent',
        value: 'localhost'
    );

    expect($pattern->supports($input))->toBeTrue();
});

it('supports a configured source', function (): void {
    $pattern = new ThreatPattern(
        group: 'ssrf',
        type: 'ssrf',
        severity: 'high',
        confidence: 88.0,
        regex: '/localhost/i',
        allowedSources: ['uri', 'query', 'body'],
    );

    $input = new ThreatInput(
        source: 'body',
        name: 'callback_url',
        value: 'http://localhost'
    );

    expect($pattern->supports($input))->toBeTrue();
});

it('rejects an unconfigured source', function (): void {
    $pattern = new ThreatPattern(
        group: 'ssrf',
        type: 'ssrf',
        severity: 'high',
        confidence: 88.0,
        regex: '/0\.0\.0\.0/',
        allowedSources: ['uri', 'query', 'body'],
    );

    $input = new ThreatInput(
        source: 'header',
        name: 'User-Agent',
        value: 'Chrome/150.0.0.0'
    );

    expect($pattern->supports($input))->toBeFalse();
});

it('distinguishes configuration group from reported type', function (): void {
    $pattern = new ThreatPattern(
        group: 'scanner_path_probe',
        type: 'bot_scanner',
        severity: 'medium',
        confidence: 75.0,
        regex: '#/phpmyadmin(?:/|$|\?)#i',
        allowedSources: ['uri', 'path'],
    );

    expect($pattern->group)
            ->toBe('scanner_path_probe')
            ->and($pattern->type)
            ->toBe('bot_scanner');
});

it('retains the configured severity and confidence', function (): void {
    $pattern = new ThreatPattern(
        group: 'rce',
        type: 'remote_code_execution',
        severity: 'critical',
        confidence: 98.0,
        regex: '/\bsystem\s*\(/i',
        allowedSources: ['query', 'body'],
    );

    expect($pattern->severity)
            ->toBe('critical')
            ->and($pattern->confidence)
            ->toBe(98.0);
});
