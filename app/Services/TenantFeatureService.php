<?php

namespace App\Services;

class TenantFeatureService
{
    /**
     * Expand a feature list with any configured dependencies.
     *
     * @param  array<int, string>  $features
     * @return array<int, string>
     */
    public function expand(array $features): array
    {
        $expanded = array_values(array_unique(array_filter($features, 'is_string')));
        $queue = $expanded;

        while ($queue !== []) {
            $feature = array_shift($queue);

            if (! is_string($feature)) {
                continue;
            }

            $requiredFeatures = (array) data_get(config('themes.features'), "{$feature}.requires", []);

            foreach ($requiredFeatures as $requiredFeature) {
                if (! is_string($requiredFeature) || in_array($requiredFeature, $expanded, true)) {
                    continue;
                }

                $expanded[] = $requiredFeature;
                $queue[] = $requiredFeature;
            }
        }

        return $expanded;
    }

    /**
     * Determine whether the given feature is available.
     *
     * @param  array<int, string>|null  $features
     */
    public function hasFeature(?array $features, string $feature): bool
    {
        return in_array($feature, $this->expand($features ?? []), true);
    }

    /**
     * Normalize a feature payload against the configured catalog.
     *
     * @param  array<int, string>|null  $features
     * @return array<int, string>
     */
    public function normalize(?array $features): array
    {
        $catalog = array_keys(config('themes.features', []));

        return array_values(array_filter(
            $this->expand($features ?? []),
            fn (string $feature): bool => in_array($feature, $catalog, true),
        ));
    }
}
