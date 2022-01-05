<?php
kirby::plugin('mauricerenck/ogimage', [
    'routes' => [
        [
            'pattern' => '(:all)/og-image',
            'action' => function ($slug) {
                if (strpos($slug, 'de/') === 0 || strpos($slug, 'de/') === 0) {
                    $pageSlug = substr($slug, 3);
                }
                $pageSlug = (empty($pageSlug) || $pageSlug === 'de' || $pageSlug === 'en') ? '/home' : $pageSlug;
                $page = page($pageSlug);

                if (!$page) {
                    return;
                }

                $seoTitle = ($page->seoTitle()->isNotEmpty()) ? $page->seoTitle() : $page->title() ;

                $canvas = imagecreatetruecolor(1600, 900);

                // Define colors and fonts
                $black = imagecolorallocate($canvas, 0, 0, 0);
                $white = imagecolorallocate($canvas, 255, 255, 255);
                $purple = imagecolorallocate($canvas, 139, 126, 164);

                $fontRegular = __DIR__ . '/assets/GangsterGrotesk-Regular.ttf';
                $fontBold = __DIR__ . '/assets/GangsterGrotesk-Bold.ttf';

                $background = imagecreatefrompng(__DIR__ . '/assets/background.png');
                imagecopyresampled(
                    $canvas,
                    $background,
                    0,
                    0,
                    0,
                    0,
                    imagesx($background),
                    imagesy($background),
                    imagesx($background),
                    imagesy($background)
                );

                // Lead text
                $text = wordwrap($seoTitle, 15, "\n");

                $text_box = imagettfbbox(80, 0, $fontBold, $text);
                // Get your Text Width and Height
                $text_width = $text_box[2] - $text_box[0];
                $text_height = $text_box[1] - $text_box[7];

                // Calculate coordinates of the text (centered)
                // $x = (1280 / 2) - ($text_width / 2); // centered
                $y = (1000 / 2) - ($text_height / 2); // centered
                $x = 150;

                [$titleX, $titleY] = imagettftext(
                    $canvas,
                    80,
                    0,
                    $x,
                    $y,
                    $white,
                    $fontBold,
                    $text
                );

                // DESCRIPTION
                // if ($titleY <= 415) {
                //     $description = $page->seoDescription()->or($page->intro()->excerpt(200))->excerpt(200);

                //     $text = wordwrap($description, 48, "\n");
                //     imagettftext(
                //         $canvas,
                //         24,
                //         0,
                //         250,
                //         $titleY + 150,
                //         $white,
                //         $fontRegular,
                //         $text
                //     );
                // }

                // Render
                ob_start();
                imagepng($canvas);
                $body = ob_get_clean();
                imagedestroy($canvas);

                return new Response($body, 'image/png');
            }
        ],
    ]
]);
