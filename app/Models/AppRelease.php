<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppRelease extends Model
{
    public function getConnectionName()
    {
        return config('tenancy.database.central_connection');
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_prerelease' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    /**
     * Get all tenant updates for this release.
     */
    public function tenantUpdates(): HasMany
    {
        return $this->hasMany(TenantUpdate::class);
    }

    public function forceRuns(): HasMany
    {
        return $this->hasMany(AppReleaseForceRun::class, 'app_release_id');
    }

    /**
     * Normalize a version tag by stripping the leading v/V prefix.
     */
    private function normalizeVersion(string $version): string
    {
        return preg_replace('/^v/i', '', trim($version));
    }

    /**
     * Check if this version is newer than another version.
     */
    public function isNewerThan(string $version): bool
    {
        return version_compare(
            $this->normalizeVersion($this->version_tag),
            $this->normalizeVersion($version),
            '>'
        );
    }

    /**
     * Check if this version is older than another version.
     */
    public function isOlderThan(string $version): bool
    {
        return version_compare(
            $this->normalizeVersion($this->version_tag),
            $this->normalizeVersion($version),
            '<'
        );
    }

    /**
     * Get the major version number.
     */
    public function getMajorVersion(): int
    {
        return (int) explode('.', $this->normalizeVersion($this->version_tag))[0];
    }

    /**
     * Get the minor version number.
     */
    public function getMinorVersion(): int
    {
        $parts = explode('.', $this->normalizeVersion($this->version_tag));

        return isset($parts[1]) ? (int) $parts[1] : 0;
    }

    /**
     * Get the patch version number.
     */
    public function getPatchVersion(): int
    {
        $parts = explode('.', $this->normalizeVersion($this->version_tag));

        return isset($parts[2]) ? (int) $parts[2] : 0;
    }

    /**
     * Determine the release type based on version change.
     */
    public function getReleaseType(?string $previousVersion = null): string
    {
        if (! $previousVersion) {
            return 'initial';
        }

        $prevVersion = $this->normalizeVersion($previousVersion);
        $currVersion = $this->normalizeVersion($this->version_tag);

        $prevParts = explode('.', $prevVersion);
        $currParts = explode('.', $currVersion);

        if (($currParts[0] ?? 0) > ($prevParts[0] ?? 0)) {
            return 'major';
        }

        if (($currParts[1] ?? 0) > ($prevParts[1] ?? 0)) {
            return 'minor';
        }

        return 'patch';
    }

    /**
     * Get a badge color based on release type.
     */
    public function getBadgeColor(): string
    {
        if ($this->is_required) {
            return 'red';
        }

        if ($this->is_prerelease) {
            return 'yellow';
        }

        $type = $this->getReleaseType();

        return match ($type) {
            'major' => 'purple',
            'minor' => 'blue',
            'patch' => 'green',
            default => 'gray',
        };
    }
}
