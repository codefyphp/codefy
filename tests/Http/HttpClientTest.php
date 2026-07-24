<?php

use Codefy\Framework\Http\HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

it('does not follow redirects by default', function (): void {
    $mock = new MockHandler([
        new Response(
            302,
            ['Location' => 'https://example.com/final']
        ),
        new Response(200, [], 'Final response'),
    ]);

    $client = HttpClient::factory([
        'handler' => HandlerStack::create($mock),
    ]);

    $response = $client->request(
        'GET',
        'https://example.com/start'
    );

    expect($response->getStatusCode())->toBe(302);
});

it('allows redirects when explicitly enabled', function (): void {
    $mock = new MockHandler([
        new Response(
            302,
            ['Location' => 'https://example.com/final']
        ),
        new Response(200, [], 'Final response'),
    ]);

    $client = HttpClient::factory([
        'handler' => HandlerStack::create($mock),
    ]);

    $response = $client->request(
        'GET',
        'https://example.com/start',
        [
            'allow_redirects' => true,
        ]
    );

    expect($response->getStatusCode())->toBe(200)
        ->and((string) $response->getBody())
        ->toBe('Final response');
});
