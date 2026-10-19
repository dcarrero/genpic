<?php

namespace App\Http\Controllers;

use App\Models\ArtisticStyle;

class StylesController extends Controller
{
    public function index()
    {
        $styles = ArtisticStyle::active()->get();
        $title = __('ui.seo_styles_title');
        $description = __('ui.seo_styles_description');

        return view('styles.index', compact('styles', 'title', 'description'));
    }

    public function show(string $styleKey)
    {
        $style = ArtisticStyle::where('style_key', $styleKey)->where('active', true)->firstOrFail();
        $otherStyles = ArtisticStyle::active()->where('id', '!=', $style->id)->inRandomOrder()->take(6)->get();

        $label = $style->t('label');
        $title = __('ui.seo_style_title', ['style' => $label]);
        $description = __('ui.seo_style_description', ['style' => $label]);

        return view('styles.show', compact('style', 'otherStyles', 'title', 'description'));
    }
}
