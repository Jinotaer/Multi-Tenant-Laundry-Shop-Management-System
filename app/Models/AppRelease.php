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

    /**
     * Check if this version is newer than another version.
     */
    public function isNewerThan(string $version): bool
    {
        $thisVersion = ltrim($this->version_tag, 'v');
        $compareVersion = ltrim($version, 'v');

        return version_compare($thisVersion, $compareVersion, '>');
    }

    /**
     * Check if this version is older than another version.
     */
    public function isOlderThan(string $version): bool
    {
        $thisVersion = ltrim($this->version_tag, 'v');
        $compareVersion = ltrim($version, 'v');

        return version_compare($thisVersion, $compareVersion, '<');
    }

    /**
     * Get the major version number.
     */
    public function getMajorVersion(): int
    {
        $version = ltrim($this->version_tag, 'v');

        return (int) explode('.', $version)[0];
    }

    /**
     * Get the minor version number.
     */
    public function getMinorVersion(): int
    {
        $version = ltrim($this->version_tag, 'v');
        $parts = explode('.', $version);

        return isset($parts[1]) ? (int) $parts[1] : 0;
    }

    /**
     * Get the patch version number.
     */
    public function getPatchVersion(): int
    {
        $version = ltrim($this->version_tag, 'v');
        $parts = explode('.', $version);

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

        $prevVersion = ltrim($previousVersion, 'v');
        $currVersion = ltrim($this->version_tag, 'v');

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
