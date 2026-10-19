<?php

namespace App\Services;

use Imagick;

class ImageMetadataService
{
    /**
     * Aplica metadatos IPTC, XMP y Comment a una imagen Imagick.
     *
     * @param Imagick $imagick     Instancia nativa de Imagick
     * @param string  $styleName   Nombre del estilo artístico (ej: "Renacimiento Clásico")
     * @param string  $formatLabel Descripción del formato (ej: "Web HD 2048px")
     * @param string  $locale      Idioma: 'es' o 'en'
     */
    public static function apply(Imagick $imagick, string $styleName = '', string $formatLabel = '', string $locale = 'es'): void
    {
        $year = date('Y');
        $company = config('genpic.company.name');
        $copyright = "© {$year} GenPic — {$company}";
        $t = self::translations($locale);

        $description = $styleName
            ? "{$t['portrait']} — {$styleName} — GenPic.com"
            : "{$t['portrait']} — GenPic.com";

        $imagick->commentImage("GenPic.com — {$t['generator']} | {$copyright}");

        $iptc = self::buildIptcProfile($copyright, $styleName, $description, $t, $locale);
        $imagick->setImageProfile('iptc', $iptc);

        $xmp = self::buildXmpProfile($copyright, $styleName, $description, $formatLabel, $year, $t, $locale);
        $imagick->setImageProfile('xmp', $xmp);
    }

    /**
     * Aplica metadatos a contenido binario de imagen (JPG/PNG).
     */
    public static function applyToContent(string $content, string $styleName = '', string $formatLabel = '', string $locale = 'es'): string
    {
        $imagick = new Imagick();
        $imagick->readImageBlob($content);

        self::apply($imagick, $styleName, $formatLabel, $locale);

        return $imagick->getImageBlob();
    }

    private static function translations(string $locale): array
    {
        return match ($locale) {
            'en' => [
                'portrait'       => 'AI-generated artistic portrait',
                'generator'      => 'AI Portrait Generator',
                'headline'       => 'AI-generated artistic portrait',
                'title'          => 'GenPic AI Portrait',
                'instructions'   => 'AI-generated image',
                'usage_terms'    => 'Personal use license. Commercial use requires written permission from ' . config('genpic.company.name') . '.',
                'kw_portrait'    => 'AI portrait',
                'kw_ai'          => 'artificial intelligence',
                'kw_art'         => 'digital art',
            ],
            'fr' => [
                'portrait'       => 'Portrait artistique généré par IA',
                'generator'      => 'Générateur de portraits IA',
                'headline'       => 'Portrait artistique généré par intelligence artificielle',
                'title'          => 'Portrait IA GenPic',
                'instructions'   => 'Image générée par intelligence artificielle',
                'usage_terms'    => 'Licence d\'utilisation personnelle. L\'utilisation commerciale nécessite l\'autorisation écrite de ' . config('genpic.company.name') . '.',
                'kw_portrait'    => 'portrait IA',
                'kw_ai'          => 'intelligence artificielle',
                'kw_art'         => 'art numérique',
            ],
            'pt' => [
                'portrait'       => 'Retrato artístico gerado por IA',
                'generator'      => 'Gerador de retratos com IA',
                'headline'       => 'Retrato artístico gerado por inteligência artificial',
                'title'          => 'Retrato IA GenPic',
                'instructions'   => 'Imagem gerada por inteligência artificial',
                'usage_terms'    => 'Licença de uso pessoal. O uso comercial requer autorização escrita da ' . config('genpic.company.name') . '.',
                'kw_portrait'    => 'retrato IA',
                'kw_ai'          => 'inteligência artificial',
                'kw_art'         => 'arte digital',
            ],
            'it' => [
                'portrait'       => 'Ritratto artistico generato con IA',
                'generator'      => 'Generatore di ritratti con IA',
                'headline'       => 'Ritratto artistico generato con intelligenza artificiale',
                'title'          => 'Ritratto IA GenPic',
                'instructions'   => 'Immagine generata da intelligenza artificiale',
                'usage_terms'    => 'Licenza per uso personale. L\'uso commerciale richiede l\'autorizzazione scritta di ' . config('genpic.company.name') . '.',
                'kw_portrait'    => 'ritratto IA',
                'kw_ai'          => 'intelligenza artificiale',
                'kw_art'         => 'arte digitale',
            ],
            default => [
                'portrait'       => 'Retrato artístico generado con IA',
                'generator'      => 'Generador de retratos con IA',
                'headline'       => 'Retrato artístico generado con inteligencia artificial',
                'title'          => 'Retrato IA GenPic',
                'instructions'   => 'Imagen generada por inteligencia artificial',
                'usage_terms'    => 'Licencia de uso personal. El uso comercial requiere autorización escrita de ' . config('genpic.company.name') . '.',
                'kw_portrait'    => 'retrato IA',
                'kw_ai'          => 'inteligencia artificial',
                'kw_art'         => 'arte digital',
            ],
        };
    }

    private static function buildIptcProfile(string $copyright, string $styleName, string $description, array $t, string $locale): string
    {
        $iptc = '';
        $iptc .= self::iptcTag(2, 116, $copyright);                             // Copyright Notice
        $iptc .= self::iptcTag(2, 80,  'GenPic.com');                            // By-line (author)
        $iptc .= self::iptcTag(2, 120, $description);                           // Caption/Abstract
        $iptc .= self::iptcTag(2, 110, 'GenPic — ' . config('genpic.company.name')); // Credit
        $iptc .= self::iptcTag(2, 115, config('app.url'));                       // Source
        $iptc .= self::iptcTag(2, 5,   $t['title']);                             // Object Name (title)
        $iptc .= self::iptcTag(2, 105, $t['headline']);                          // Headline
        $iptc .= self::iptcTag(2, 40,  $t['instructions']);                      // Special Instructions

        // Keywords en el idioma del usuario
        $iptc .= self::iptcTag(2, 25, $t['kw_portrait']);
        $iptc .= self::iptcTag(2, 25, 'GenPic');
        $iptc .= self::iptcTag(2, 25, $t['kw_ai']);
        $iptc .= self::iptcTag(2, 25, $t['kw_art']);
        if ($styleName) {
            $iptc .= self::iptcTag(2, 25, $styleName);
        }

        return $iptc;
    }

    private static function buildXmpProfile(
        string $copyright, string $styleName, string $description, string $formatLabel, string $year, array $t, string $locale
    ): string {
        $e = fn (string $s) => htmlspecialchars($s, ENT_XML1, 'UTF-8');

        $keywordsXml = "<rdf:li>{$e($t['kw_portrait'])}</rdf:li>
            <rdf:li>GenPic</rdf:li>
            <rdf:li>{$e($t['kw_ai'])}</rdf:li>
            <rdf:li>{$e($t['kw_art'])}</rdf:li>";
        if ($styleName) {
            $keywordsXml .= "\n            <rdf:li>{$e($styleName)}</rdf:li>";
        }

        $appUrl = config('app.url');
        $credit = $e('GenPic — ' . config('genpic.company.name'));
        $lang = $locale;

        return '<?xpacket begin="" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
 <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
  <rdf:Description
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:xmpRights="http://ns.adobe.com/xap/1.0/rights/"
   xmlns:photoshop="http://ns.adobe.com/photoshop/1.0/"
   xmlns:xmp="http://ns.adobe.com/xap/1.0/">
   <dc:creator>
    <rdf:Seq><rdf:li>' . $credit . '</rdf:li></rdf:Seq>
   </dc:creator>
   <dc:rights>
    <rdf:Alt><rdf:li xml:lang="' . $lang . '">' . $e($copyright) . '</rdf:li></rdf:Alt>
   </dc:rights>
   <dc:description>
    <rdf:Alt><rdf:li xml:lang="' . $lang . '">' . $e($description) . '</rdf:li></rdf:Alt>
   </dc:description>
   <dc:title>
    <rdf:Alt><rdf:li xml:lang="' . $lang . '">' . $e($t['title']) . ($styleName ? " — {$e($styleName)}" : '') . '</rdf:li></rdf:Alt>
   </dc:title>
   <dc:subject>
    <rdf:Bag>
     ' . $keywordsXml . '
    </rdf:Bag>
   </dc:subject>
   <xmpRights:WebStatement>' . $appUrl . '/terms</xmpRights:WebStatement>
   <xmpRights:Marked>True</xmpRights:Marked>
   <xmpRights:UsageTerms>
    <rdf:Alt><rdf:li xml:lang="' . $lang . '">' . $e($t['usage_terms']) . '</rdf:li></rdf:Alt>
   </xmpRights:UsageTerms>
   <photoshop:Credit>' . $credit . '</photoshop:Credit>
   <photoshop:Source>' . $appUrl . '</photoshop:Source>
   <xmp:CreatorTool>GenPic.com — ' . $e($t['generator']) . '</xmp:CreatorTool>
  </rdf:Description>
 </rdf:RDF>
</x:xmpmeta>
<?xpacket end="w"?>';
    }

    private static function iptcTag(int $rec, int $tag, string $value): string
    {
        $len = strlen($value);
        if ($len >= 0x8000) return '';
        return chr(0x1C) . chr($rec) . chr($tag) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF) . $value;
    }
}
