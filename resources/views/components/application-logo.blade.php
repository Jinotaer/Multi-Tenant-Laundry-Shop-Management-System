@props(['logoUrl' => null])
@php
    $tenant = function_exists('tenant') ? tenant() : null;

    if ($tenant && $tenant->hasFeature('custom_branding') && $tenant->logo_path) {
        $resolvedUrl = route('stancl.tenancy.asset', ['path' => $tenant->logo_path]);
    } elseif ($logoUrl) {
        $resolvedUrl = $logoUrl;
    } elseif (!$tenant) {
        $admin = \App\Models\Admin::first();
        $resolvedUrl = ($admin && $admin->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($admin->logo_path))
            ? asset('storage/' . $admin->logo_path)
            : global_asset('Laundry3.png');
    } else {
        $resolvedUrl = global_asset('Laundry3.png');
    }
@endphp
<img src="{{ $resolvedUrl }}" alt="Application Logo" {{ $attributes }} />
