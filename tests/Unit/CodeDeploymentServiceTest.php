<?php

use App\Services\CodeDeploymentService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

function invokeDeploymentManifest(CodeDeploymentService $service, string $path): array
{
    $method = new ReflectionMethod($service, 'buildDeploymentManifest');
    $method->setAccessible(true);

    /** @var array{directories: array<int, string>, files: array<int, string>} $manifest */
    $manifest = $method->invoke($service, $path);

    return $manifest;
}

function invokeManagedDirectorySync(
    CodeDeploymentService $service,
    string $source,
    string $destination,
    string $relativePath,
    string $operation = 'deployment'
): void {
    $method = new ReflectionMethod($service, 'syncManagedDirectory');
    $method->setAccessible(true);
    $method->invoke($service, $source, $destination, $relativePath, $operation);
}

function invokeManagedFileSync(
    CodeDeploymentService $service,
    string $source,
    string $destination,
    string $relativePath,
    string $operation = 'deployment'
): void {
    $method = new ReflectionMethod($service, 'syncManagedFile');
    $method->setAccessible(true);
    $method->invoke($service, $source, $destination, $relativePath, $operation);
}

function invokeDeploymentCheck(CodeDeploymentService $service, string $command): void
{
    $method = new ReflectionMethod($service, 'runDeploymentCheck');
    $method->setAccessible(true);
    $method->invoke($service, $command);
}

function invokeShellCommand(
    CodeDeploymentService $service,
    string $command,
    ?callable $onHeartbeat = null,
    ?callable $onOutput = null,
    ?string $label = null
): array {
    $method = new ReflectionMethod($service, 'runShellCommand');
    $method->setAccessible(true);

    /** @var array{exit_code: int, output: string} $result */
    $result = $method->invoke($service, $command, $onHeartbeat, $onOutput, $label);

    return $result;
}

test('deployment manifest includes deployable release roots and excludes local-only paths', function () {
    $root = storage_path('framework/testing/deployment-manifest-' . Str::random(12));

    File::makeDirectory("{$root}/app", 0755, true);
    File::makeDirectory("{$root}/resources/views", 0755, true);
    File::makeDirectory("{$root}/tests/Feature", 0755, true);
    File::makeDirectory("{$root}/storage/logs", 0755, true);
    File::makeDirectory("{$root}/vendor/bin", 0755, true);
    File::makeDirectory("{$root}/node_modules/vite", 0755, true);
    File::makeDirectory("{$root}/.github/workflows", 0755, true);

    File::put("{$root}/artisan", '<?php');
    File::put("{$root}/composer.json", '{}');
    File::put("{$root}/boost.json", '{}');
    File::put("{$root}/.gitignore", "*\n!.gitignore\n");
    File::put("{$root}/.env", 'APP_KEY=base64:test');

    try {
        $manifest = invokeDeploymentManifest(app(CodeDeploymentService::class), $root);

        expect($manifest['directories'])
            ->toContain('app')
            ->toContain('resources')
            ->toContain('tests')
            ->not->toContain('storage')
            ->not->toContain('vendor')
            ->not->toContain('node_modules')
            ->not->toContain('.github');

        expect($manifest['files'])
            ->toContain('artisan')
            ->toContain('composer.json')
            ->toContain('boost.json')
            ->toContain('.gitignore')
            ->not->toContain('.env');
    } finally {
        File::deleteDirectory($root);
    }
});

test('deployment manifest fails when archive has no deployable entries', function () {
    $root = storage_path('framework/testing/deployment-manifest-empty-' . Str::random(12));

    File::makeDirectory("{$root}/storage/framework", 0755, true);
    File::makeDirectory("{$root}/vendor/bin", 0755, true);
    File::put("{$root}/.env", 'APP_KEY=base64:test');

    try {
        expect(fn () => invokeDeploymentManifest(app(CodeDeploymentService::class), $root))
            ->toThrow(RuntimeException::class, 'did not contain any deployable files');
    } finally {
        File::deleteDirectory($root);
    }
});

test('managed directory sync copies dotfiles and removes stale files without deleting the live directory first', function () {
    $root = storage_path('framework/testing/deployment-sync-' . Str::random(12));
    $source = "{$root}/source/public";
    $destination = "{$root}/destination/public";

    File::makeDirectory("{$source}/js", 0755, true);
    File::makeDirectory("{$destination}/js", 0755, true);
    File::makeDirectory("{$destination}/css", 0755, true);

    File::put("{$source}/.htaccess", "RewriteEngine On\n");
    File::put("{$source}/js/app.js", 'console.log("new");');
    File::put("{$destination}/js/old.js", 'console.log("old");');
    File::put("{$destination}/css/app.css", 'body{}');

    try {
        invokeManagedDirectorySync(
            app(CodeDeploymentService::class),
            $source,
            $destination,
            'public'
        );

        expect(File::exists("{$destination}/.htaccess"))->toBeTrue();
        expect(File::get("{$destination}/js/app.js"))->toBe('console.log("new");');
        expect(File::exists("{$destination}/js/old.js"))->toBeFalse();
        expect(File::exists("{$destination}/css"))->toBeFalse();
    } finally {
        File::deleteDirectory($root);
    }
});

test('managed file sync can replace a directory with a release file', function () {
    $root = storage_path('framework/testing/deployment-file-sync-' . Str::random(12));
    $source = "{$root}/source/artisan";
    $destination = "{$root}/destination/artisan";

    File::makeDirectory($destination, 0755, true);
    File::ensureDirectoryExists(dirname($source));
    File::put($source, '<?php echo "artisan";');

    try {
        invokeManagedFileSync(
            app(CodeDeploymentService::class),
            $source,
            $destination,
            'artisan'
        );

        expect(is_file($destination))->toBeTrue();
        expect(File::get($destination))->toBe('<?php echo "artisan";');
    } finally {
        File::deleteDirectory($root);
    }
});

test('deployment command plan runs install and build steps before recaching and queue restart', function () {
    config()->set('updates.deployment.run_composer_install', true);
    config()->set('updates.deployment.run_npm_build', true);
    config()->set('updates.deployment.run_database_migrations', true);
    config()->set('updates.deployment.run_tenant_migrations', true);
    config()->set('updates.deployment.run_queue_restart', true);

    $commands = app(CodeDeploymentService::class)->getDeploymentCommandPlan(true);

    expect($commands)->toBe([
        'php artisan cache:clear',
        'php artisan config:clear',
        'php artisan route:clear',
        'php artisan view:clear',
        'composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader',
        'npm ci --no-audit --no-fund',
        'verify frontend dependencies',
        'npm audit fix --no-fund',
        'npm run build',
        'verify public/build/manifest.json',
        'php artisan migrate --force',
        'php artisan tenants:migrate --force',
        'php artisan config:cache',
        'php artisan route:cache',
        'php artisan view:cache',
        'php artisan queue:restart',
    ]);
});

test('tenant scoped deployment command plan excludes central-only commands', function () {
    config()->set('updates.deployment.run_composer_install', false);
    config()->set('updates.deployment.run_npm_build', false);
    config()->set('updates.deployment.run_database_migrations', true);
    config()->set('updates.deployment.run_tenant_migrations', true);
    config()->set('updates.deployment.run_queue_restart', true);

    $commands = app(CodeDeploymentService::class)->getDeploymentCommandPlan(false);

    expect($commands)->toBe([
        'php artisan cache:clear',
        'php artisan config:clear',
        'php artisan route:clear',
        'php artisan view:clear',
        'php artisan config:cache',
        'php artisan route:cache',
        'php artisan view:cache',
    ]);
});

test('frontend dependency verification fails when required npm files are missing', function () {
    $tailwindPreflight = base_path('node_modules/tailwindcss/lib/css/preflight.css');
    $viteDevServerIndex = base_path('node_modules/laravel-vite-plugin/dist/dev-server-index.html');
    $tailwindBackup = null;
    $viteBackup = null;

    try {
        if (File::exists($tailwindPreflight)) {
            $tailwindBackup = File::get($tailwindPreflight);
            File::delete($tailwindPreflight);
        }

        if (File::exists($viteDevServerIndex)) {
            $viteBackup = File::get($viteDevServerIndex);
            File::delete($viteDevServerIndex);
        }

        expect(fn () => invokeDeploymentCheck(app(CodeDeploymentService::class), 'verify frontend dependencies'))
            ->toThrow(RuntimeException::class, 'Frontend dependency verification failed');
    } finally {
        if ($tailwindBackup !== null) {
            File::ensureDirectoryExists(dirname($tailwindPreflight));
            File::put($tailwindPreflight, $tailwindBackup);
        }

        if ($viteBackup !== null) {
            File::ensureDirectoryExists(dirname($viteDevServerIndex));
            File::put($viteDevServerIndex, $viteBackup);
        }
    }
});

test('shell command heartbeats stream progress for long running commands', function () {
    $heartbeats = [];
    $chunks = [];

    $command = sprintf(
        "\"%s\" -r \"fwrite(STDOUT, 'booting' . PHP_EOL); usleep(6200000); fwrite(STDOUT, 'done' . PHP_EOL);\"",
        PHP_BINARY
    );

    $result = invokeShellCommand(
        app(CodeDeploymentService::class),
        $command,
        function (string $message) use (&$heartbeats): void {
            $heartbeats[] = $message;
        },
        function (string $chunk) use (&$chunks): void {
            $chunks[] = $chunk;
        },
        'test command'
    );

    expect($result['exit_code'])->toBe(0);
    expect($result['output'])->toContain('done');
    expect($heartbeats)->not->toBeEmpty();
    expect(implode('', $chunks))->toContain('done');
});
