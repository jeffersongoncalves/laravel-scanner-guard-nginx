<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Denylist Path
    |--------------------------------------------------------------------------
    |
    | Relative to base_path(). `include` it from your nginx server block. The
    | file is written to a temp file and renamed over the target, so nginx
    | never reads a half-written list, and only when its content changed.
    |
    */
    'path' => env('SCANNER_GUARD_NGINX_PATH', 'storage/app/scanner-guard/denylist.conf'),

    /*
    |--------------------------------------------------------------------------
    | Reload Command
    |--------------------------------------------------------------------------
    |
    | nginx only re-reads an included file on reload. Runs (e.g. "sudo nginx
    | -s reload") only when the file content changed; a non-zero exit or a
    | timeout is logged, never thrown, and the next sync runs it again.
    | Null = don't reload.
    |
    */
    'reload_command' => env('SCANNER_GUARD_NGINX_RELOAD_COMMAND'),

];
