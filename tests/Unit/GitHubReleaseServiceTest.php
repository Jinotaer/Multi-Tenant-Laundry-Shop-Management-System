<?php

use App\Models\AppRelease;
use App\Services\GitHubReleaseService;
use Tests\TestCase;

uses(TestCase::class);

test('compare versions uses semantic version ordering instead of publish order assumptions', function () {
    $service = app(GitHubReleaseService::class);

    expect($service->compareVersions('v1.1.0', 'v1.0.9'))->toBeGreaterThan(0)
        ->and($service->compareVersions('v1.0.9', 'v1.1.0'))->toBeLessThan(0)
        ->and($service->compareVersions('v1.0.0', 'v1.0.0'))->toBe(0);
});

test('sort releases descending returns the highest semantic version first', function () {
    $service = app(GitHubReleaseService::class);

    $releases = [
        new AppRelease(['version_tag' => 'v1.0.9', 'published_at' => now()]),
        new AppRelease(['version_tag' => 'v1.1.0', 'published_at' => now()->subDays(2)]),
        new AppRelease(['version_tag' => 'v1.0.10', 'published_at' => now()->subDay()]),
    ];

    $sorted = $service->sortReleasesDescending($releases);

    expect($sorted->pluck('version_tag')->all())->toBe([
        'v1.1.0',
        'v1.0.10',
        'v1.0.9',
    ]);
});
