<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Autocomplete extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $wireModel,
        public string $searchMethod,
        public string $placeholder = 'Search...',
        public string $label = '',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.autocomplete');
    }
}
