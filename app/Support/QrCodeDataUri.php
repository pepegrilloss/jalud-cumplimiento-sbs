<?php

namespace App\Support;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeDataUri
{
    public static function fromText(string $text, int $size = 320): ?string
    {
        if (! class_exists(QrCode::class)) {
            return null;
        }

        $qrCode = new QrCode(
            data: $text,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $svg = (new SvgWriter)->write($qrCode)->getString();

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
