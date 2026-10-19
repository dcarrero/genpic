<?php

namespace App\Services\ImageGeneration;

/**
 * Centraliza la construcción de prompts para todos los proveedores IA.
 * Un solo lugar para controlar las reglas de preservación facial y restricciones.
 */
class PromptBuilder
{
    private const SAFETY_RULES = 'MOST IMPORTANT — FRAMING: '
        . 'Keep the EXACT same zoom level, framing, crop and camera distance as the input photo. '
        . 'Do NOT zoom in. Do NOT zoom out. Do NOT reframe. Do NOT change the field of view. '
        . 'The output image must show the EXACT same amount of scene as the input — same margins on all sides, same body coverage. '
        . 'If the input shows full body, output must show full body at the same scale. If input shows head and shoulders, output must show head and shoulders at the same scale. '
        . 'RULES: '
        . '1) Keep every subject at the EXACT same vertical and horizontal position. NEVER crop or cut off heads. '
        . '2) NEVER change skin color, ethnicity, race or skin tone. '
        . '3) Keep EXACT facial features, bone structure, eye color, hair color and hairstyle. '
        . '4) Keep the EXACT same number of people. Do NOT add, remove, merge or duplicate anyone. '
        . '5) Keep each person in their EXACT same position and pose. '
        . '6) Do NOT use real brand names or logos. '
        . '7) Each person must remain physically separate and anatomically correct. '
        . '8) Framing preservation is MORE important than style. ';

    private const STYLE_INSTRUCTION = 'Transform ONLY clothing, shoes, footwear, accessories and background. '
        . 'KEEP the EXACT same zoom, framing, crop and distance as the original photo. ';

    /** Términos anti-crop que se añaden a todos los negative prompts. */
    private const NEGATIVE_FRAMING = 'zoomed in, close-up, cropped head, cut off head, head out of frame, top of head missing, shifted subject, repositioned subject, changed framing, different zoom level';

    /**
     * Construye el prompt completo con reglas de seguridad + estilo.
     */
    public static function build(string $stylePrompt): string
    {
        return self::SAFETY_RULES . self::STYLE_INSTRUCTION . 'Apply this style: ' . $stylePrompt;
    }

    /**
     * Enriquece el negative prompt del estilo con términos anti-crop.
     */
    public static function negativePrompt(string $styleNegative): string
    {
        return self::NEGATIVE_FRAMING . ', ' . $styleNegative;
    }
}
