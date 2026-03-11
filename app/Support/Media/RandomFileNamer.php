<?php

namespace App\Support\Media;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Support\FileNamer\FileNamer;

/**
 * Generates a 32-character random hex string as the filename (without extension).
 * The extension is preserved and appended by the media library.
 *
 * Example output: 8f3a1c9d2e7b0a4f6d5e8c3b1a2f9e7d
 */
class RandomFileNamer extends FileNamer
{
    public function originalFileName(string $fileName): string
    {
        return Str::random(32);
    }

    public function conversionFileName(string $fileName, Conversion $conversion): string
    {
        return Str::random(32).'-'.$conversion->getName();
    }

    public function responsiveFileName(string $fileName): string
    {
        return Str::random(32);
    }
}
