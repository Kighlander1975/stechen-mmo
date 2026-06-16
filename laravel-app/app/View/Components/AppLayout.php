<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public function __construct(
        public ?string $headerEyebrow = null,
        public ?string $headerTitle = null,
        public ?string $headerStatusLabel = null,
        public ?string $headerStatusTone = null,
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
