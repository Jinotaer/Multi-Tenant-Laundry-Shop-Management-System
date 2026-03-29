<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | The default theme preset applied when no theme has been selected.
    |
    */

    'default' => 'indigo',

    /*
    |--------------------------------------------------------------------------
    | Theme Presets
    |--------------------------------------------------------------------------
    |
    | Each preset defines the Tailwind CSS classes used for themed UI elements
    | across both admin and tenant layouts.
    |
    */

    'presets' => [

        'indigo' => [
            'label' => 'Indigo',
            'preview' => '#6366f1',
            'nav_active_bg' => 'bg-indigo-50',
            'nav_active_text' => 'text-indigo-600',
            'avatar_bg' => 'bg-indigo-100',
            'avatar_text' => 'text-indigo-600',
            'primary_bg' => 'bg-indigo-600',
            'primary_hover' => 'hover:bg-indigo-700',
            'badge_bg' => 'bg-indigo-100',
            'badge_text' => 'text-indigo-800',
            'focus_ring' => 'focus:ring-indigo-500',
        ],

        'blue' => [
            'label' => 'Blue',
            'preview' => '#3b82f6',
            'nav_active_bg' => 'bg-blue-50',
            'nav_active_text' => 'text-blue-600',
            'avatar_bg' => 'bg-blue-100',
            'avatar_text' => 'text-blue-600',
            'primary_bg' => 'bg-blue-600',
            'primary_hover' => 'hover:bg-blue-700',
            'badge_bg' => 'bg-blue-100',
            'badge_text' => 'text-blue-800',
            'focus_ring' => 'focus:ring-blue-500',
        ],

        'emerald' => [
            'label' => 'Emerald',
            'preview' => '#10b981',
            'nav_active_bg' => 'bg-emerald-50',
            'nav_active_text' => 'text-emerald-600',
            'avatar_bg' => 'bg-emerald-100',
            'avatar_text' => 'text-emerald-600',
            'primary_bg' => 'bg-emerald-600',
            'primary_hover' => 'hover:bg-emerald-700',
            'badge_bg' => 'bg-emerald-100',
            'badge_text' => 'text-emerald-800',
            'focus_ring' => 'focus:ring-emerald-500',
        ],

        'purple' => [
            'label' => 'Purple',
            'preview' => '#a855f7',
            'nav_active_bg' => 'bg-purple-50',
            'nav_active_text' => 'text-purple-600',
            'avatar_bg' => 'bg-purple-100',
            'avatar_text' => 'text-purple-600',
            'primary_bg' => 'bg-purple-600',
            'primary_hover' => 'hover:bg-purple-700',
            'badge_bg' => 'bg-purple-100',
            'badge_text' => 'text-purple-800',
            'focus_ring' => 'focus:ring-purple-500',
        ],

        'rose' => [
            'label' => 'Rose',
            'preview' => '#f43f5e',
            'nav_active_bg' => 'bg-rose-50',
            'nav_active_text' => 'text-rose-600',
            'avatar_bg' => 'bg-rose-100',
            'avatar_text' => 'text-rose-600',
            'primary_bg' => 'bg-rose-600',
            'primary_hover' => 'hover:bg-rose-700',
            'badge_bg' => 'bg-rose-100',
            'badge_text' => 'text-rose-800',
            'focus_ring' => 'focus:ring-rose-500',
        ],

        'slate' => [
            'label' => 'Slate',
            'preview' => '#64748b',
            'nav_active_bg' => 'bg-slate-100',
            'nav_active_text' => 'text-slate-800',
            'avatar_bg' => 'bg-slate-200',
            'avatar_text' => 'text-slate-700',
            'primary_bg' => 'bg-slate-700',
            'primary_hover' => 'hover:bg-slate-800',
            'badge_bg' => 'bg-slate-100',
            'badge_text' => 'text-slate-800',
            'focus_ring' => 'focus:ring-slate-500',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Available feature flags that can be toggled per tenant by the admin.
    |
    */

    'features' => [
        'basic_tracking' => [
            'label' => 'Basic Tracking',
            'description' => 'Basic workflow: Received → In Progress → Ready → Claimed.',
            'category' => 'operations',
            'requires' => [],
        ],
        'advanced_workflow' => [
            'label' => 'Advanced Workflow',
            'description' => 'Extended statuses with granular tracking (Washing, Drying, Folding, etc.).',
            'category' => 'operations',
            'requires' => ['basic_tracking'],
        ],
        'simple_pricing' => [
            'label' => 'Simple Pricing',
            'description' => 'Price per kilo only.',
            'category' => 'pricing',
            'requires' => [],
        ],
        'advanced_pricing' => [
            'label' => 'Advanced Pricing',
            'description' => 'Per kilo, per item, per load, flat-rate pricing and service bundles.',
            'category' => 'pricing',
            'requires' => ['simple_pricing'],
        ],
        'customer_portal' => [
            'label' => 'Customer Portal',
            'description' => 'Customer live status page for order tracking.',
            'category' => 'customer',
            'requires' => [],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'description' => 'Email and in-app real-time alerts for order updates.',
            'category' => 'communication',
            'requires' => [],
        ],
        'reports' => [
            'label' => 'Reports & Analytics',
            'description' => 'PDF & Excel financial reports with business insights.',
            'category' => 'analytics',
            'requires' => [],
        ],
        'analytics_dashboard' => [
            'label' => 'Analytics Dashboard',
            'description' => 'Business performance insights and trend analysis.',
            'category' => 'analytics',
            'requires' => ['reports'],
        ],
        'expense_tracking' => [
            'label' => 'Expense Tracking',
            'description' => 'Operational cost logging and profit estimation.',
            'category' => 'finance',
            'requires' => [],
        ],
        'customer_loyalty' => [
            'label' => 'Customer Loyalty',
            'description' => 'Points and stamp rewards system for repeat customers.',
            'category' => 'customer',
            'requires' => [],
        ],
        'custom_branding' => [
            'label' => 'Custom Branding',
            'description' => 'Custom receipt and portal branding with your shop logo.',
            'category' => 'branding',
            'requires' => [],
        ],
        'online_payments' => [
            'label' => 'Online Payments',
            'description' => 'Allow customers to pay online.',
            'category' => 'finance',
            'requires' => [],
        ],
        'sms_notifications' => [
            'label' => 'SMS Notifications',
            'description' => 'Send SMS updates to customers.',
            'category' => 'communication',
            'requires' => ['notifications'],
        ],
        'inventory_management' => [
            'label' => 'Inventory Management',
            'description' => 'Track supplies and inventory levels.',
            'category' => 'operations',
            'requires' => [],
        ],
        'priority_support' => [
            'label' => 'Priority Support',
            'description' => 'Priority support channel with faster response times.',
            'category' => 'support',
            'requires' => [],
        ],
    ],

];
