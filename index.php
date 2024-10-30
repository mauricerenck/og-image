<?php

namespace mauricerenck\OgImage;

use Kirby\Http\Response;
use Kirby\Cms\App as Kirby;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('mauricerenck/ogimage', [
    'pageMethods' => require_once __DIR__ . '/libs/page-methods.php',
    'routes' => [
        [
            'pattern' => '(:all)/og-image',
            'language' => '*',
            'action' => function ($lang, $slug) {
                $page = ($slug == $lang) ? site()->homePage() : $page = page($slug);

                $imageWidth = option('mauricerenck.ogimage.width', 1600);
                $imageHeight = option('mauricerenck.ogimage.height', 900);

                if (!$page) {
                    return new Response('Page "' . $slug . '" not found', 'text/plain', 404);
                }

                if ($page->ogimage()->isNotEmpty()) {
                    return new Response($page->ogimage()->toFile()->crop($imageWidth, $imageHeight)->read(), 'image/png');
                }

                if ($page->hasGeneratedOgImage()) {
                    return new Response($page->image('generated-og-image.' . $lang . '.png')->read(), 'image/png');
                }

                try {
                    $page->createOgImage();
                    return new Response($page->image('generated-og-image.' . $lang . '.png')->read(), 'image/png');
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            },
        ],
    ],
]);
