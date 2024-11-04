<?php

namespace mauricerenck\OgImage;

use Kirby\Http\Response;
use Kirby\Cms\App as Kirby;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('mauricerenck/ogimage', [
    'pageMethods' => require_once __DIR__ . '/lib/page-methods.php',
    'routes' => [
        [
            'pattern' => ['og-image'],
            'action' => function () {
                $page = site()->homePage();

                if ($page->hasOgImage()) {
                    return new Response($page->getOgImage()->read(), 'image/png');
                }

                if ($page->hasGeneratedOgImage()) {
                    return new Response($page->image('generated-og-image.default.png')->read(), 'image/png');
                }

                try {
                    $page->createOgImage();
                    return new Response($page->image('generated-og-image.default.png')->read(), 'image/png');
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), 'text/plain', 500);
                }
            }
        ],
        [
            'pattern' => ['(:all)/og-image', 'og-image'],
            'action' => function ($slug) {
                $languages = kirby()->languages();
                $language = null;
                if (count($languages) > 1) {
                    $language = kirby()->language()->code();
                    $slugParts = explode('/', $slug);

                    if (in_array($slugParts[0], $languages->codes())) {
                        $language = $slugParts[0];
                        $slug = implode('/', array_slice($slugParts, 1));
                    }
                }

                $languageString = is_null($language) ? 'default' : $language;
                $page = ($slug == '/' || $slug == 'og-image') ? site()->homePage() : $page = page($slug);

                if (!$page) {
                    return new Response('Page "' . $slug . '" not found', 'text/plain', 404);
                }

                if ($page->hasOgImage()) {
                    return new Response($page->getOgImage()->read(), 'image/png');
                }

                if ($page->hasGeneratedOgImage()) {
                    return new Response($page->image('generated-og-image.' . $languageString . '.png')->read(), 'image/png');
                }

                try {
                    $page->createOgImage();
                    return new Response($page->image('generated-og-image.' . $languageString . '.png')->read(), 'image/png');
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), 'text/plain', 500);
                }
            },
        ],
    ],
]);
