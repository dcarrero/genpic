<?php

namespace App\Http\Controllers;

use App\Models\ArtisticStyle;
use App\Models\GalleryExample;
use Illuminate\Support\Collection;

class LandingController extends Controller
{
    /**
     * Pares antes/después reales para las landings.
     *
     * Es el activo más fuerte del producto (36 pares taxonomizados) y no
     * aparecía en ninguna landing: todas reciclaban la misma foto de stock.
     * Si no hay ejemplos del tipo pedido, devuelve una colección vacía y la
     * vista degrada a su variante sin fotografía — nunca a un placeholder.
     */
    private function examples(string $type, int $limit = 6): Collection
    {
        // scopeActive() ya ordena por sort_order.
        // with(): <x-before-after> lee $example->artisticStyle para la etiqueta;
        // sin eager load son hasta 6 queries extra por landing.
        return GalleryExample::query()
            ->with('artisticStyle')
            ->active()
            ->ofType($type)
            ->limit($limit)
            ->get();
    }

    public function index()
    {
        $styles = ArtisticStyle::active()->get();
        $description = __('ui.seo_landing_description');

        return view('landing', compact('styles', 'description'));
    }

    public function professional()
    {
        $styles = ArtisticStyle::where('style_category', 'professional')->where('active', true)->orderBy('sort_order')->get();
        $title = __('ui.lp_professional_title');
        $description = __('ui.lp_professional_meta');
        $examples = $this->examples('individual', 4);


        return view('landings.professional', compact('styles', 'title', 'description', 'examples'));
    }

    public function valentines()
    {
        $styles = ArtisticStyle::where('style_category', 'thematic')->where('active', true)->orderBy('sort_order')->get();
        $title = __('ui.lp_valentines_title');
        $description = __('ui.lp_valentines_meta');
        $examples = $this->examples('couple', 6);


        return view('landings.valentines', compact('styles', 'title', 'description', 'examples'));
    }

    public function linkedin()
    {
        // La vista (Conversational FAQ) no pinta ningún estilo: se retira la
        // query de 'linkedin_photo', que era una consulta por request sin uso.
        $title = __('ui.lp_linkedin_title');
        $description = __('ui.lp_linkedin_meta');
        $examples = $this->examples('individual', 4);


        return view('landings.linkedin', compact('title', 'description', 'examples'));
    }

    public function friends()
    {
        $styles = ArtisticStyle::active()->get()->shuffle()->take(8);
        $title = __('ui.lp_friends_title');
        $description = __('ui.lp_friends_meta');
        $examples = $this->examples('group', 3);


        return view('landings.friends', compact('styles', 'title', 'description', 'examples'));
    }

    public function family()
    {
        $styles = ArtisticStyle::active()->get()->shuffle()->take(8);
        $title = __('ui.lp_family_title');
        $description = __('ui.lp_family_meta');
        $examples = $this->examples('family', 3); // la vista (Letter) pinta first() + slice(1,2)


        return view('landings.family', compact('styles', 'title', 'description', 'examples'));
    }

    public function tinder()
    {
        $styles = ArtisticStyle::where('style_category', 'thematic')->where('active', true)->orderBy('sort_order')->get();
        $title = __('ui.lp_tinder_title');
        $description = __('ui.lp_tinder_meta');
        $examples = $this->examples('individual', 4);


        return view('landings.tinder', compact('styles', 'title', 'description', 'examples'));
    }

    public function gift()
    {
        $styles = ArtisticStyle::active()->get()->shuffle()->take(8);
        $title = __('ui.lp_gift_title');
        $description = __('ui.lp_gift_meta');
        $examples = $this->examples('family', 4);


        return view('landings.gift', compact('styles', 'title', 'description', 'examples'));
    }

    public function photoToPainting()
    {
        $styles = ArtisticStyle::whereIn('style_category', ['historic', 'artistic'])->where('active', true)->orderBy('sort_order')->get();
        $title = __('ui.lp_painting_title');
        $description = __('ui.lp_painting_meta');
        $examples = $this->examples('individual', 6);


        return view('landings.painting', compact('styles', 'title', 'description', 'examples'));
    }

    public function mothersDay()
    {
        $styles = ArtisticStyle::active()->get()->shuffle()->take(8);
        $title = __('ui.lp_mothers_title');
        $description = __('ui.lp_mothers_meta');
        $examples = $this->examples('family', 4);


        return view('landings.mothers-day', compact('styles', 'title', 'description', 'examples'));
    }

    public function cvPhoto()
    {
        // La vista (Narrative Workflow) no pinta ningún estilo: query retirada.
        $title = __('ui.lp_cv_title');
        $description = __('ui.lp_cv_meta');
        $examples = $this->examples('individual', 3);


        return view('landings.cv', compact('title', 'description', 'examples'));
    }
}
