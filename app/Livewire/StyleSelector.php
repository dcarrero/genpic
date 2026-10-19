<?php

namespace App\Livewire;

use App\Models\ArtisticStyle;
use App\Services\CachedQueries;
use Livewire\Component;

class StyleSelector extends Component
{
    public ?string $selectedStyle = null;
    public string $search = '';
    public bool $showDropdown = false;

    public function mount(?string $style = null)
    {
        $this->selectedStyle = $style;
    }

    public function selectStyle(string $styleKey)
    {
        $this->selectedStyle = $styleKey;
        $this->showDropdown = false;
        $this->search = '';
        $this->dispatch('style-selected', styleKey: $styleKey);
    }

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;
        $this->search = '';
    }

    public function render()
    {
        $locale = app()->getLocale();
        $styles = CachedQueries::activeStyles();

        // Filtrar por búsqueda
        if ($this->search) {
            $search = mb_strtolower($this->search);
            $styles = $styles->filter(function ($style) use ($search, $locale) {
                $label = mb_strtolower($style->t('label'));
                $desc = mb_strtolower($style->t('description'));
                return str_contains($label, $search) || str_contains($desc, $search);
            });
        }

        $selected = $this->selectedStyle
            ? CachedQueries::styleByKey($this->selectedStyle)
            : null;

        return view('livewire.style-selector', [
            'styles' => $styles,
            'selected' => $selected,
        ]);
    }
}
