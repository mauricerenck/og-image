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
                $siteLanguage = kirby()->currentLanguage();

                $languageString = (is_null($siteLanguage)) ? 'default' : $siteLanguage->code();

                if ($page->hasOgImage()) {
                    return new Response($page->getOgImage()->read(), 'image/png');
                }

                if ($page->hasGeneratedOgImage($languageString)) {
                    return new Response($page->image('generated-og-image.' . $languageString . '.png')->read(), 'image/png');
                }

                try {
                    $page->createOgImage($languageString);
                    return new Response($page->image('generated-og-image.' . $languageString . '.png')->read(), 'image/png');
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), 'text/plain', 500);
                }
            }
        ],
        [
            'pattern' => ['(:all)/og-image'],
            'action' => function ($slug) {
                $siteLanguage = kirby()->currentLanguage();
                $languageString = (is_null($siteLanguage)) ? 'default' : $siteLanguage->code();

                if (kirby()->multilang()) {
                    $languages = kirby()->languages();
                    $slugParts = explode('/', $slug);

                    if (in_array($slugParts[0], $languages->codes())) {
                        $slug = implode('/', array_slice($slugParts, 1));
                    }
                }

                $page = ($slug == '/' || $slug == 'og-image') ? site()->homePage() : page($slug);

                if (!$page) {
                    return new Response('Page "' . $slug . '" not found', 'text/plain', 404);
                }

                if ($page->hasOgImage()) {
                    return new Response($page->getOgImage()->read(), 'image/png');
                }

                if ($page->hasGeneratedOgImage($languageString)) {
                    return new Response($page->image('generated-og-image.' . $languageString . '.png')->read(), 'image/png');
                }

                try {
                    $page->createOgImage($languageString);
                    return new Response($page->image('generated-og-image.' . $languageString . '.png')->read(), 'image/png');
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), 'text/plain', 500);
                }
            },
        ],
    ],
]);
