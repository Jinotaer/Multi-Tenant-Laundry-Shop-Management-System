<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\Admin;

class FaviconController extends Controller
{
    public function tenantFavicon()
    {
        $tenant = function_exists('tenant') ? tenant() : null;
        
        if ($tenant && $tenant->hasFeature('custom_branding') && $tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
            $imgData = Storage::disk('public')->get($tenant->logo_path);
            $mime = Storage::disk('public')->mimeType($tenant->logo_path);
        } else {
            $imgData = file_exists(public_path('Laundry3.png')) ? file_get_contents(public_path('Laundry3.png')) : '';
            $mime = 'image/png';
        }

        if (!$imgData) abort(404);

        $base64 = base64_encode($imgData);
        $dataUri = "data:$mime;base64,$base64";
        
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><clipPath id="circle"><circle cx="50" cy="50" r="50"/></clipPath></defs><image width="100" height="100" preserveAspectRatio="xMidYMid slice" href="'.$dataUri.'" clip-path="url(#circle)"/></svg>';

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    public function globalFavicon()
    {
        $admin = Admin::first();
        
        if ($admin && $admin->logo_path && Storage::disk('public')->exists($admin->logo_path)) {
            $imgData = Storage::disk('public')->get($admin->logo_path);
            $mime = Storage::disk('public')->mimeType($admin->logo_path);
        } else {
            $imgData = file_exists(public_path('Laundry3.png')) ? file_get_contents(public_path('Laundry3.png')) : '';
            $mime = 'image/png';
        }

        if (!$imgData) abort(404);

        $base64 = base64_encode($imgData);
        $dataUri = "data:$mime;base64,$base64";
        
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><clipPath id="circle"><circle cx="50" cy="50" r="50"/></clipPath></defs><image width="100" height="100" preserveAspectRatio="xMidYMid slice" href="'.$dataUri.'" clip-path="url(#circle)"/></svg>';

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
