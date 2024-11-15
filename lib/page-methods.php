<?php

namespace mauricerenck\OgImage;

return [
    'hasOgImage' => function () {
        $ogImageField = option('mauricerenck.ogimage.field', 'ogImage');
        return (!is_null($this->{$ogImageField}()) && $this->{$ogImageField}()->isNotEmpty());
    },
    'hasGeneratedOgImage' => function (string $language) {
        $filename = 'generated-og-image.' . $language . '.png';
        $savedOgImage = !is_null($this->image($filename)) && $this->image($filename)->exists();

        return $savedOgImage;
    },
    'getOgImage' => function () {
        $ogImageField = option('mauricerenck.ogimage.field', 'ogImage');
        $imageWidth = option('mauricerenck.ogimage.width', 1600);
        $imageHeight = option('mauricerenck.ogimage.height', 900);

        return (!is_null($this->{$ogImageField}()) && $this->{$ogImageField}()->isNotEmpty())
            ? $this->{$ogImageField}()->toFile()->crop($imageWidth, $imageHeight)
            : null;
    },
    'createOgImage' => function (string $language) {
        $imageWidth = option('mauricerenck.ogimage.width', 1600);
        $imageHeight = option('mauricerenck.ogimage.height', 900);

        $font = option('mauricerenck.ogimage.font.path', null);
        $fontColor = option('mauricerenck.ogimage.font.color', [0, 0, 0]);
        $fontSize = option('mauricerenck.ogimage.font.size', 80);
        $fontLineHeight = option('mauricerenck.ogimage.font.lineheight', 2);

        $templateImagePath = option('mauricerenck.ogimage.image.template', __DIR__ . '/../assets/template.png');

        $heroImageField = option('mauricerenck.ogimage.heroImage.field', 'hero');
        $heroImageCropSize = option('mauricerenck.ogimage.heroImage.cropsize', [600, 600]);
        $heroImagePosition = option('mauricerenck.ogimage.heroImage.position', [0, 0]);
        $heroImageFallbackColor = option('mauricerenck.ogimage.heroImage.fallbackColor', [255, 123, 123]);
        $heroImageFallbackImage = option('mauricerenck.ogimage.heroImage.fallbackImage', null);

        $titleField = option('mauricerenck.ogimage.title.field', 'title');
        $titlePosition = option('mauricerenck.ogimage.title.position', [0, 0]);
        $titleCharactersPerLine = option('mauricerenck.ogimage.title.charactersPerLine', 20);

        if (is_null($font)) {
            return;
        }

        $canvas = imagecreatetruecolor($imageWidth, $imageHeight);
        $textColor = imagecolorallocate($canvas, $fontColor[0], $fontColor[1], $fontColor[2]);
        $title = $this->{$titleField}()->isNotEmpty() ? $this->{$titleField}() : $this->title();
        $templateImage = imagecreatefrompng($templateImagePath);

        $backgroundFile = !is_null($this->{$heroImageField}()) && $this->{$heroImageField}()->isNotEmpty()
            ? $this->{$heroImageField}()->toFile()->crop($heroImageCropSize[0], $heroImageCropSize[1])
            : null;

        if (!is_null($backgroundFile)) {
            $filename = $backgroundFile->root();

            switch ($backgroundFile->mime()) {
                case 'image/jpeg':
                    $background = imagecreatefromjpeg($filename);
                    break;
                case 'image/png':
                    $background = imagecreatefrompng($filename);
                    break;
                case 'image/webp':
                    $background = imagecreatefromwebp($filename);
                    break;
                default:
                    $background = imagecreatefrompng($filename);
                    break;
            }

            imagecopyresampled(
                $canvas,
                $background,
                $heroImagePosition[0],
                $heroImagePosition[1],
                0,
                0,
                imagesx($background),
                imagesy($background),
                imagesx($background),
                imagesy($background)
            );
        } else if (!is_null($heroImageFallbackImage)) {
            $background = imagecreatefrompng($heroImageFallbackImage);

            imagecopyresampled(
                $canvas,
                $background,
                $heroImagePosition[0],
                $heroImagePosition[1],
                0,
                0,
                imagesx($background),
                imagesy($background),
                imagesx($background),
                imagesy($background)
            );
        } else {
            $color = imagecolorallocate($canvas, $heroImageFallbackColor[0], $heroImageFallbackColor[1], $heroImageFallbackColor[2]);
            imagefill($canvas, 0, 0, $color);
        }

        imagecopyresampled(
            $canvas,
            $templateImage,
            0,
            0,
            0,
            0,
            imagesx($templateImage),
            imagesy($templateImage),
            imagesx($templateImage),
            imagesy($templateImage)
        );

        // SET TEXT
        $imageTitle = wordwrap($title, $titleCharactersPerLine, "\n", true);
        $lines = explode("\n", $imageTitle);

        $y = $fontSize;
        foreach ($lines as $line) {
            imagettftext($canvas, $fontSize, 0, $titlePosition[0], $titlePosition[1] + $y, $textColor, $font, $line);
            $y += $fontSize * $fontLineHeight; // Increase the y position for the next line
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'png');
        imagepng($canvas, $tempFile);

        $filename = 'generated-og-image.' . $language . '.png';

        kirby()->impersonate('kirby');
        if (!is_null($this->file($filename)) && $this->file($filename)->exist()) {
            $this->file($filename)->delete($filename);
        }

        $this->createFile([
            'filename' => $filename,
            'template' => 'image',
            'source' => $tempFile,
            'parent' => $this,
            'content' => [
                'alt' => 'og-image'
            ],
        ]);

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    },
];
