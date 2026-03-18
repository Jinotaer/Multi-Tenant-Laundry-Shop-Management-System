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
            'welcome',
            'overview_stats',
            'owner_metrics',
            'recent_orders',
            'enabled_features',
        ],
    ],
    'options' => [
        'sidebar_position' => [
            'top' => [
                'label' => 'Top',
                'description' => 'Place the navigation bar at the top of the page.',
            ],
            'left' => [
                'label' => 'Left',
                'description' => 'Keep the primary navigation on the left side.',
            ],
            'right' => [
                'label' => 'Right',
                'description' => 'Move the navigation rail to the right side.',
            ],
        ],
        'topbar_behavior' => [
            'sticky' => [
                'label' => 'Sticky',
                'description' => 'Keep the topbar visible while the page scrolls.',
            ],
            'static' => [
                'label' => 'Static',
                'description' => 'Let the topbar scroll away with the page content.',
            ],
        ],
        'topbar_style' => [
            'minimal' => [
                'label' => 'Minimal',
                'description' => 'Use a clean, flat header with no card treatment.',
            ],
            'card' => [
                'label' => 'Card',
                'description' => 'Wrap the topbar in a rounded surface with border and shadow.',
            ],
            'accent' => [
                'label' => 'Accent',
                'description' => 'Add a theme-colored top accent and a more branded header surface.',
            ],
        ],
        'sidebar_style' => [
            'solid' => [
                'label' => 'Solid',
                'description' => 'Use a full-height dashboard sidebar.',
            ],
            'floating' => [
                'label' => 'Floating',
                'description' => 'Inset the sidebar with extra spacing and shadow.',
            ],
            'compact' => [
                'label' => 'Compact',
                'description' => 'Use an icon rail on desktop and a drawer on mobile.',
            ],
        ],
        'color_mode' => [
            'light' => [
                'label' => 'Light',
                'description' => 'Always use the light interface.',
            ],
            'dark' => [
                'label' => 'Dark',
                'description' => 'Always use the dark interface.',
            ],
            'system' => [
                'label' => 'System',
                'description' => 'Follow the device light or dark preference.',
            ],
        ],
        'font_size' => [
            'sm' => [
                'label' => 'Small',
                'description' => 'Tighter text scale for information-dense screens.',
                'root_font_size' => '15px',
            ],
            'base' => [
                'label' => 'Base',
                'description' => 'Balanced text scale for day-to-day dashboard work.',
                'root_font_size' => '16px',
            ],
            'lg' => [
                'label' => 'Large',
                'description' => 'Larger text scale for easier scanning and readability.',
                'root_font_size' => '17px',
            ],
        ],
        'border_radius' => [
            'md' => [
                'label' => 'Medium',
                'description' => 'Sharper corners for a denser enterprise look.',
                'css_value' => '0.75rem',
            ],
            'lg' => [
                'label' => 'Large',
                'description' => 'Balanced rounded corners across cards and panels.',
                'css_value' => '1rem',
            ],
            'xl' => [
                'label' => 'Extra Large',
                'description' => 'Softer corners for a more modern, spacious feel.',
                'css_value' => '1.5rem',
            ],
        ],
        'icon_size' => [
            'sm' => [
                'label' => 'Small',
                'description' => 'Compact icons across navigation and controls.',
                'css_size' => '1rem',
            ],
            'base' => [
                'label' => 'Base',
                'description' => 'Balanced icon size for regular dashboard use.',
                'css_size' => '1.25rem',
            ],
            'lg' => [
                'label' => 'Large',
                'description' => 'Larger icons for improved legibility.',
                'css_size' => '1.5rem',
            ],
        ],
        'icon_stroke' => [
            'thin' => [
                'label' => 'Thin',
                'description' => 'Use a lighter icon stroke weight.',
                'stroke_width' => '1.25',
            ],
            'base' => [
                'label' => 'Base',
                'description' => 'Default icon stroke weight.',
                'stroke_width' => '1.5',
            ],
            'bold' => [
                'label' => 'Bold',
                'description' => 'Use stronger icon stroke weight.',
                'stroke_width' => '2',
            ],
        ],
        'logo_visibility' => [
            '1' => [
                'label' => 'Show logo',
                'description' => 'Display the uploaded logo in the dashboard shell.',
            ],
            '0' => [
                'label' => 'Hide logo',
                'description' => 'Keep the shop name visible without rendering the logo mark.',
            ],
        ],
    ],
    'widgets' => [
        'welcome' => [
            'label' => 'Welcome',
            'description' => 'Greeting, role summary, and shop context.',
            'roles' => ['owner', 'staff'],
        ],
        'overview_stats' => [
            'label' => 'Overview Stats',
            'description' => 'Customer, order, active, and ready counts.',
            'roles' => ['owner', 'staff'],
        ],
        'owner_metrics' => [
            'label' => 'Owner Metrics',
            'description' => 'Revenue and staffing metrics for owners.',
            'roles' => ['owner'],
        ],
        'recent_orders' => [
            'label' => 'Recent Orders',
            'description' => 'Latest order activity for operational follow-up.',
            'roles' => ['owner', 'staff'],
        ],
        'enabled_features' => [
            'label' => 'Enabled Features',
            'description' => 'Current feature access for this workspace.',
            'roles' => ['owner'],
        ],
    ],
];
