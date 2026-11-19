<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use JeffersonGoncalves\ScannerGuardNginx\NginxSink;

beforeEach(function () {
    config(['scanner-guard-nginx.path' => 'storage/app/scanner-guard/denylist-test.conf']);
    $this->file = storage_path('app/scanner-guard/denylist-test.conf');
});

afterEach(function () {
    File::deleteDirectory(storage_path('app/scanner-guard'));
});

it('writes static entries and bans as nginx deny lines', function () {
    (new NginxSink)->sync([
        ['ip' => '185.177.72.0/24', 'reason' => 'static', 'expires_at' => null],
        ['ip' => '10.20.20.20', 'reason' => 'scanner_path', 'expires_at' => '2026-11-20T10:00:00+00:00'],
    ]);

    expect(File::get($this->file))->toBe(
        'deny 185.177.72.0/24; # static'.PHP_EOL.'deny 10.20.20.20; # scanner_path, expires 2026-11-20T10:00:00+00:00'.PHP_EOL
    )->and(File::glob(storage_path('app/scanner-guard/*.tmp')))->toBe([]);
});

it('writes an empty file when nothing is banned', function () {
    (new NginxSink)->sync([]);

    expect(File::get($this->file))->toBe(PHP_EOL);
});

it('runs the reload command only when the file content changed', function () {
    Process::fake();
    config(['scanner-guard-nginx.reload_command' => 'sudo nginx -s reload']);
    $entries = [['ip' => '10.20.20.23', 'reason' => 'static', 'expires_at' => null]];

    (new NginxSink)->sync($entries);
    (new NginxSink)->sync($entries);
    (new NginxSink)->sync([]);

    Process::assertRanTimes('sudo nginx -s reload', 2);
});

it('logs instead of failing when the reload command fails', function () {
    Process::fake(['*' => Process::result(errorOutput: 'nginx: [emerg]', exitCode: 1)]);
    Log::spy();
    config(['scanner-guard-nginx.reload_command' => 'sudo nginx -s reload']);

    (new NginxSink)->sync([]);

    Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => $message === 'scanner-guard: nginx reload command failed'
        && str_contains($context['error'], 'nginx: [emerg]'));
});

it('logs instead of failing when the reload command throws (e.g. a timeout)', function () {
    Process::fake(fn () => throw new RuntimeException('The process exceeded the timeout of 60 seconds.'));
    Log::spy();
    config(['scanner-guard-nginx.reload_command' => 'sudo nginx -s reload']);

    (new NginxSink)->sync([]);

    Log::shouldHaveReceived('error')->withArgs(fn (string $message, array $context) => str_contains($context['error'], 'timeout'));
});

it('is driven by scanner-guard:sync-denylist', function () {
    config([
        'scanner-guard.driver' => 'http', // no database needed: bans come from the (empty) last pull
        'scanner-guard.sinks' => [NginxSink::class],
        'scanner-guard.ip_blocklist' => ['185.177.72.69', 'not-an-ip'],
    ]);

    $this->artisan('scanner-guard:sync-denylist')->assertSuccessful();

    expect(File::get($this->file))->toBe('deny 185.177.72.69; # static'.PHP_EOL);
});
