<?php

return [
    'defaults' => [
        'sidebar_position' => 'left',
        'topbar_behavior' => 'sticky',
        'topbar_style' => 'minimal',
        'sidebar_style' => 'solid',
        'color_mode' => 'system',
        'theme' => 'indigo',
        'font_size' => 'base',
        'border_radius' => 'lg',
        'icon_size' => 'base',
        'icon_stroke' => 'base',
        'logo_visibility' => true,
        'dashboard_widget_order' => [
            'total_shops',
            'pending_registrations',
            'active_workspaces',
            'recent_shops',
        ],
    ],
    'widgets' => [
        'total_shops' => [
            'label' => 'Total Shops',
            'description' => 'The current number of created tenant workspaces.',
        ],
        'pending_registrations' => [
            'label' => 'Pending Registrations',
            'description' => 'Shop registrations waiting for admin action.',
        ],
        'active_workspaces' => [
            'label' => 'Active Workspaces',
            'description' => 'Paid or in-trial workspaces that can access their dashboard.',
        ],
        'recent_shops' => [
            'label' => 'Recent Shops',
            'description' => 'The latest tenant workspaces created in the platform.',
        ],
    ],
];
