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
