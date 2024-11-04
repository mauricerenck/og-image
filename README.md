# OG Image

#### A Kirby OpenGraph Image Plugin

![GitHub release](https://img.shields.io/github/release/mauricerenck/og-image.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-4%2B-black.svg)

---

This plugin creates an og-image for a page based on a template image and a text input. Simply add `/og-image` to any url to get the og-image for that page.

## Installation

Use one of these methods to install the plugin:

-   composer (recommended): `composer require mauricerenck/ogimage`
-   zip file: unzip [main.zip](https://github.com/mauricerenck/ogimage/releases/latest) as folder `site/plugins/ogimage`

## Prerequisites

This plugin requires the following assets to be present:

-  a ttf font file
-  a png template image

You can find a sample template image in the `assets` folder of this plugin.

## How it works

This plugins listens to `/og-image` on any page. It will go through the following steps:

1.  Check if the page has a `ogimage` field - If you have an `ogimage` field, the plugin will use the image from that field and deliver it as the og-image.
2.  Check if the page has a `hero` image - If you have a `hero` image, the plugin can use that image and place it under the template image.
3.  Use the template image and place text on it.

You can configure the position of the text, and the hero image, even crop it to position it below transparent areas of your template image.

## In your template

Add the following meta tags to your HTML `<head>` tag:

```html
<meta property="og:image" content="<?= $page->url(); ?>/og-image">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="600">
```

## OPTIONS

**Please make sure to prefix all the options with `mauricerenck.ogimage.`**.

| Option                            | Default  | Description                                                                                      |
| --------------------------------- | -------- | ------------------------------------------------------------------------------------------------ |
| `width` | `1600` | width of the resulting og-image |
| `height` | `900` | height of the resulting og-image |
| `image.template` | `./../assets/template.png` | path to your og-image template image |
| `font.path` | `''` | **mandatory** (missing font will result in an error) |
| `font.color` | `[0, 0, 0]` | color of the font [r,g,b] |
| `font.size` | `80` | size of the font |
| `heroImage.field` | `hero` | path to your og-image template image |
| `heroImage.cropSize` | `[600, 600]` | Size in pixels of the rendered hero image |
| `heroImage.position` | `[0,0]` | x,y position of the hero image on the template image |
| `heroImage.fallbackColor` | `[255, 123, 123]` | [r,g,b] color to fill the hero-image area if no image given |
| `heroImage.fallbackImage` | `null` | path to a fallback when the hero image is not given  |
| `title.field` | `title` | The name of the field your want to use as title |
| `title.position` | `[0, 0]` | [x,y] position of your text |
| `title.charactersPerLine` | `20` | Number of characters before a line break |
