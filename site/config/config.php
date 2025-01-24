<?php

return [
    'debug' => true,
    'languages' => true,
    "mauricerenck.ogimage" => [
        "font.path" => "assets/spectral-regular-webfont.ttf", // path to your ttf font relative from your document root
        "font.size" => 50,
        "font.lineheight" => 1.5,
        "image.template" => "assets/template.png", // path to your template image relative from your document root
        "title.position" => [300, 35], // x,y position of your text in pixel
        "title.charactersPerLine" => 30, // number of characters before a line break
        "heroImage.field" => 'myHero', // fieldname of the hero image
        "heroImage.cropsize" => [738, 465], // size in pixels of the rendered hero image
        "heroImage.position" => [429, 287], // x,y position of the hero image on the template image
        "heroImage.fallbackColor" => [3, 105, 161], // r,g,b color to fill the hero-image area if no image given
        "heroImage.fallbackImage" => "assets/template.png", // OR path to a fallback when the hero image is not given
    ],
];
