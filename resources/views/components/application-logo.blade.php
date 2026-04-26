@php
    $tenant = function_exists('tenant') ? tenant() : null;
    $logoUrl = global_asset('Laundry3.png');

    // If we are in a tenant context and they have a custom logo uploaded
    if ($tenant && $tenant->hasFeature('custom_branding') && $tenant->logo_path) {
        $logoUrl = route('stancl.tenancy.asset', ['path' => $tenant->logo_path]);
    }
@endphp
<img src="{{ $logoUrl }}" alt="Application Logo" {{ $attributes }} />
