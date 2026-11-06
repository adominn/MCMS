<?php

declare(strict_types=1);

namespace App\Modules\Credentials;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

// IR-11: the QR code opens the verification page directly in the phone's own camera app.
final class Qr
{
    public static function svg(string $text, int $size = 160): string
    {
        $svg = (new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd)))->writeString($text);

        return preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }
}
