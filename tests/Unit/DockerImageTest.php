<?php

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

test('the container serves built assets and supports both demo roles', function (string $email, int $createStatus): void {
    $cookies = new CookieJar;
    $client = new Client([
        'base_uri' => getenv('DOCKER_SMOKE_URL'),
        'cookies' => $cookies,
        'http_errors' => false,
        'allow_redirects' => false,
        'timeout' => 15,
    ]);

    expect($client->get('/up')->getStatusCode())->toBe(200);
    $login = $client->get('/login');
    expect($login->getStatusCode())->toBe(200);

    preg_match_all('~(?:src|href)="([^"]*/build/[^" ]+\.(?:js|css))"~', (string) $login->getBody(), $assets);
    expect($assets[1])->not->toBeEmpty();
    foreach (array_unique($assets[1]) as $asset) {
        expect($client->get($asset)->getStatusCode())->toBe(200);
    }

    $csrfCookie = $cookies->getCookieByName('XSRF-TOKEN');
    expect($csrfCookie)->not->toBeNull();
    $response = $client->post('/login', [
        'headers' => ['X-XSRF-TOKEN' => urldecode($csrfCookie->getValue())],
        'form_params' => ['email' => $email, 'password' => 'password'],
    ]);
    expect($response->getStatusCode())->toBe(302);
    expect($client->get('/dashboard')->getStatusCode())->toBe(200);

    $assetsPage = $client->get('/assets');
    expect($assetsPage->getStatusCode())->toBe(200);
    expect((string) $assetsPage->getBody())->toContain('AST-00001');
    expect($client->get('/assets/create')->getStatusCode())->toBe($createStatus);
})->with([
    'manager' => ['test@example.com', 200],
    'employee' => ['mitarbeiter@example.com', 403],
])->skip(fn (): bool => ! getenv('DOCKER_SMOKE_URL'), 'Requires a running, isolated Docker demo.');
