<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ScannerGuardNginx;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use JeffersonGoncalves\ScannerGuard\Contracts\DenylistSink;
use Throwable;

/**
 * Writes the denylist as nginx `deny <ip>;` lines and reloads nginx when the
 * file changed. Entries arrive validated, so every line passes `nginx -t`.
 */
class NginxSink implements DenylistSink
{
    public function sync(array $entries): void
    {
        $path = base_path((string) config('scanner-guard-nginx.path', 'storage/app/scanner-guard/denylist.conf'));

        $contents = implode(PHP_EOL, array_map(
            fn (array $entry): string => $entry['expires_at'] === null
                ? "deny {$entry['ip']}; # {$entry['reason']}"
                : "deny {$entry['ip']}; # {$entry['reason']}, expires {$entry['expires_at']}",
            $entries,
        )).PHP_EOL;

        if (File::exists($path) && File::get($path) === $contents) {
            return;
        }

        File::ensureDirectoryExists(dirname($path));

        // Temp file + rename: an nginx reload mid-write never reads a truncated file.
        $temp = $path.'.'.bin2hex(random_bytes(4)).'.tmp';
        File::put($temp, $contents);
        File::move($temp, $path);

        $this->reload();
    }

    protected function reload(): void
    {
        $command = config('scanner-guard-nginx.reload_command');

        if ($command === null || $command === '') {
            return;
        }

        try {
            $result = Process::run((string) $command);
            $error = $result->failed() ? 'exit '.$result->exitCode().': '.trim($result->errorOutput() ?: $result->output()) : null;
        } catch (Throwable $e) {
            // Timeout or the command can't start: the file is already written, so only report it.
            $error = $e->getMessage();
        }

        if ($error !== null) {
            Log::error('scanner-guard: nginx reload command failed', ['command' => $command, 'error' => $error]);
        }
    }
}
