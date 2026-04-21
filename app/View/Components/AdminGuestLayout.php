<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AdminGuestLayout extends Component
{
    public function __construct(
        public bool $showColorModeToggle = true,
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.admin-guest');
    }
}
