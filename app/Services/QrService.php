<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrService
{
    /** SVG needs no PHP image extension. High error correction survives scuffed or small prints. */
    public function svg(string $url, int $size = 300, bool $inline = true): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));

        // bacon-qr-code v3 uses an enum, v2 uses static methods.
        $level = enum_exists(ErrorCorrectionLevel::class) ? ErrorCorrectionLevel::H : ErrorCorrectionLevel::H();

        $svg = $writer->writeString($url, 'utf-8', $level);

        // Drop the XML prolog when embedding in HTML.
        return $inline ? preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg) : $svg;
    }
}
