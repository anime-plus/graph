<?php
class UserControllerExportModule extends AbstractUserControllerModule
{
    public static function getText(ViewContext $viewContext, $media)
    {
    }

    public static function getUrlParts()
    {
        return ['export'];
    }

    public static function url()
    {
        $args = func_get_args();
        $userName = array_shift($args);
        $media = array_shift($args);
        $settings = array_shift($args);

        $url = '/' . $userName . '/' . str_replace('=', '', base64_encode($settings)) . '/' . time() . '.png';
        return UrlHelper::absoluteUrl($url);
    }

    public static function getMediaAvailability()
    {
        return [];
    }

    public static function getOrder()
    {
    }

    public static function getContentType()
    {
        return 'image/png';
    }

    const IMAGE_TYPE_ANIME = 1;
    const IMAGE_TYPE_MANGA = 2;
    const IMAGE_TYPE_ANIME_MANGA = 3;

    const COLOR_BARS1 = 1;
    const COLOR_BARS2 = 2;
    const COLOR_BAR_GUIDES1 = 3;
    const COLOR_BAR_GUIDES2 = 4;
    const COLOR_BACKGROUND = 5;
    const COLOR_FONT_DARK = 6;
    const COLOR_FONT_LIGHT = 7;
    const COLOR_TITLE = 8;
    const COLOR_LOGO = 9;

    private static function mixColors($c1, $c2, $r)
    {
        $c3 = 0;
        for ($i = 0; $i < 4; $i ++)
        {
            $comp = ($c1 & 0xff) + (($c2 & 0xff) - ($c1 & 0xff)) * $r;
            $c3 |= ($comp << ($i << 3));
            $c1 >>= 8;
            $c2 >>= 8;
        }
        return $c3;
    }

    private static function drawGradient($img, $x1, $y1, $x2, $y2, $c1, $c2)
    {
        if ($x2 < $x1)
        {
            list($x1, $x2, $c1, $c2) =
                [$x2, $x1, $c2, $c1];
        }
        $delta = 1. / max(1, $x2 - $x1);
        for ($x = $x1, $r = 0; $x <= $x2; $x ++, $r += $delta)
        {
            $c = self::mixColors($c1, $c2, $r);
            imageline($img, $x, $y1, $x, $y2, $c);
        }
    }

    private static function drawCopy($dst, $src, $x, $y)
    {
        imagecopy($dst, $src, $x, $y, 0, 0, imagesx($src), imagesy($src));
    }

    private static function getBoundingBox($fontSize, $fontPath, $text)
    {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $ret = new StdClass;
        $ret->x1 = $bbox[6];
        $ret->x2 = $bbox[2];
        $ret->y1 = $bbox[5];
        $ret->y2 = $bbox[1];
        $ret->x = $ret->x1;
        $ret->y = -$ret->y1;
        $ret->width = abs($ret->x2 - $ret->x1);
        $ret->height = abs($ret->y2 - $ret->y1);
        return $ret;
    }



    private static function getIconImage($settings)
    {
        $iconImageMask = imagecreatefrompng(Config::$mediaDirectory . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'export-mask.png');
        $img = imagecreatetruecolor(imagesx($iconImageMask), imagesy($iconImageMask));
        imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $settings->colors[self::COLOR_BACKGROUND]);
        imagealphablending($img, false);
        for ($y = 0; $y < imagesy($img); $y ++)
        {
            for ($x = 0; $x < imagesx($img); $x ++)
            {
                $r = imagecolorat($iconImageMask, $x, $y);
                $r &= 0xff;
                $r /= 255.0;
                $c = self::mixColors($settings->colors[self::COLOR_BACKGROUND], $settings->colors[self::COLOR_LOGO], $r);
                imagesetpixel($img, $x, $y, $c);
            }
        }
        return $img;
    }



    private static function getBarsImage($settings, $distribution, $mirror = false)
    {
        $groups = $distribution->getGroupsSizes(AbstractDistribution::IGNORE_NULL_KEY);
        $max = $distribution->getLargestGroupSize(AbstractDistribution::IGNORE_NULL_KEY);
        $img = imagecreatetruecolor($settings->barWidth, ($settings->barHeight + $settings->barPadding) * count($groups));
        imagealphablending($img, false);
        $gx1 = $mirror
            ? imagesx($img)
            : 0;
        $gx2 = imagesx($img) - $gx1;
        self::drawGradient($img, $gx1, 0, $gx2, imagesy($img), $settings->colors[self::COLOR_BARS1], $settings->colors[self::COLOR_BARS2]);

        $i = 0;
        foreach ($groups as $count)
        {
            ++ $i;
            $y1 = ($i - 1) * ($settings->barHeight + $settings->barPadding);
            $y2 = $i * ($settings->barHeight + $settings->barPadding);
            $x1 = $mirror
                ? imagesx($img) - $count * imagesx($img) / max(1, $max)
                : $count * imagesx($img) / max(1, $max);
            $x2 = $mirror
                ? 0
                : imagesx($img);
            imagefilledrectangle($img, $x1, $y1, $x2, $y2, $settings->colors[self::COLOR_BACKGROUND]);
        }
        foreach ($distribution->getGroupsSizes(AbstractDistribution::IGNORE_NULL_KEY) as $i => $count)
        {
            $y1 = $i * ($settings->barHeight + $settings->barPadding);
            $y2 = $i * ($settings->barHeight + $settings->barPadding) + $settings->barPadding - 1;
            self::drawGradient($img, $gx1, $y1, $gx2, $y2, $settings->colors[self::COLOR_BAR_GUIDES1], $settings->colors[self::COLOR_BAR_GUIDES2]);
        }
        return $img;
    }



    private static function getBarsAxesImage($settings, $center = false)
    {
        $margin = 6;
        $texts = array_map(function($x) { return " $x "; }, range(10, 1));
        $w = 0;
        foreach ($texts as $text)
        {
            $w = max($w, self::getBoundingBox($settings->fontSizeNormal, $settings->font, $text)->width);
        }
        $w += $margin;
        $img = imagecreatetruecolor($w, ($settings->barHeight + $settings->barPadding) * count($texts));
        imagealphablending($img, false);
        imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $settings->colors[self::COLOR_BACKGROUND]);
        imagealphablending($img, true);

        $i = 0;
        foreach ($texts as $text)
        {
            $bbox = self::getBoundingBox($settings->fontSizeNormal, $settings->font, $text);
            if ($center)
            {
                $x = ($w - $bbox->width) / 2.0;
            }
            else
            {
                $x = $w - $bbox->width - $margin;
            }
            $y = $i * ($settings->barHeight + $settings->barPadding);
            $y += ($settings->barHeight + $settings->barPadding - $bbox->height) >> 1;
            $x += $bbox->x;
            $y += $bbox->y;
            imagettftext($img, $settings->fontSizeNormal, 0, $x, $y, $settings->colors[self::COLOR_FONT_DARK], $settings->font, $text);
            ++ $i;
        }
        return $img;
    }



    private static function getBarsBandsImage($settings, $distribution, $mirror = false)
    {
        $margin = 6;
        $distributionArray = [];
        foreach ($distribution->getGroupsKeys(AbstractDistribution::IGNORE_NULL_KEY) as $key)
        {
            $distributionArray[$key] = $distribution->getGroupSize($key);
        }
        $percentages = TextHelper::roundPercentages($distributionArray);
        $texts1 = array_map(function($x) { return " $x "; }, $distributionArray);
        $texts2 = array_map(function($x) { return " ($x%) "; }, $percentages);
        $bbox1 = [];
        $bbox2 = [];
        $w = 0;
        foreach (array_keys($texts1) as $key)
        {
            $text1 = $texts1[$key];
            $text2 = $texts2[$key];
            $bbox1[$key] = self::getBoundingBox($settings->fontSizeNormal, $settings->font, $text1);
            $bbox2[$key] = self::getBoundingBox($settings->fontSizeSmall, $settings->font, $text2);
            $w = max($w, $bbox1[$key]->width + $bbox2[$key]->width);
        }
        $w += $margin;
        $img = imagecreatetruecolor($w, ($settings->barHeight + $settings->barPadding) * count($percentages));
        imagealphablending($img, false);
        imagefilledrectangle($img, 0, 0, imagesx($img), imagesy($img), $settings->colors[self::COLOR_BACKGROUND]);
        imagealphablending($img, true);

        $bbox1max = max(array_map(function($bbox) { return $bbox->width; }, $bbox1));
        $bbox2max = max(array_map(function($bbox) { return $bbox->width; }, $bbox2));
        $i = 0;
        foreach (array_keys($texts1) as $key)
        {
            $text1 = $texts1[$key];
            $text2 = $texts2[$key];
            $x = $mirror
                ? $bbox2max + $bbox1max - $bbox1[$key]->width
                : $margin + $bbox1max - $bbox1[$key]->width;
            $x += $bbox1[$key]->x;
            $yb = $i * ($settings->barHeight + $settings->barPadding);
            $y = $yb;
            $y += ($settings->barHeight + $settings->barPadding - $bbox1[$key]->height) >> 1;
            $y += $bbox1[$key]->y;
            imagettftext($img, $settings->fontSizeNormal, 0, $x, $y, $settings->colors[self::COLOR_FONT_DARK], $settings->font, $text1);

            $x = $mirror
                ? $bbox2max - $bbox2[$key]->width
                : $margin + $bbox1max;
            $x += $bbox2[$key]->x;
            $y = $yb;
            $y += ($settings->barHeight + $settings->barPadding - $bbox2[$key]->height) >> 1;
            $y += $bbox2[$key]->y;
            imagettftext($img, $settings->fontSizeSmall, 0, $x, $y, $settings->colors[self::COLOR_FONT_LIGHT], $settings->font, $text2);

            ++ $i;
        }
        return $img;
    }



    private static function getFooterImage($settings, $distribution, $media, $mirror = false)
    {
        $text = 'mean rated:';
        $bboxSmall = self::getBoundingBox($settings->fontSizeNormal, $settings->font, $text);
        $text = join(' ', array_map(function($x) { return ucfirst(Media::toString($x)); }, Media::getConstList()));
        $bboxBig = self::getBoundingBox($settings->fontSizeBig, $settings->font, $text);

        $w = $settings->barWidth;
        $h = $bboxBig->height;
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagefilledrectangle($img, 0, 0, $w, $h, $settings->colors[self::COLOR_BACKGROUND]);
        imagealphablending($img, true);

        $text = ' ' . ucfirst(Media::toString($media));
        $bbox = self::getBoundingBox($settings->fontSizeBig, $settings->font, 'Anime  ');
        $x = $bbox->x + ($mirror ? $w - $bbox->width : 0);
        $y = $bbox->y + (($h - $bboxBig->height) >> 1);
        imagettftext($img, $settings->fontSizeBig, 0, $x, $y, $settings->colors[self::COLOR_TITLE], $settings->font, $text);
        $lx = $mirror ? $w - $bbox->width : $bbox->width;

        $text1 = 'mean: ';
        $text2 = sprintf('%.02f', $distribution->getMeanScore());
        $bbox1 = self::getBoundingBox($settings->fontSizeNormal, $settings->font, $text1);
        $bbox2 = self::getBoundingBox($settings->fontSizeBig, $settings->font, $text2);
        $x = $bbox1->x + ($mirror ? 0 : ($w - $bbox1->width - $bbox2->width));
        $y = $bbox2->y/*ugly but works*/ + (($bbox2->height - $bboxSmall->height) >> 1);
        imagettftext($img, $settings->fontSizeNormal, 0, $x, $y, $settings->colors[self::COLOR_FONT_LIGHT], $settings->font, $text1);
        $x += $bbox1->width + $bbox2->x;
        $y = $bboxBig->y + (($h - $bboxBig->height) >> 1);
        imagettftext($img, $settings->fontSizeBig, 0, $x, $y, $settings->colors[self::COLOR_FONT_DARK], $settings->font, $text2);
        $rx = $mirror ? $bbox1->width + $bbox2->width : $w - $bbox1->width - $bbox2->width;

        $text1 = 'rated: ';
        $text2 = $distribution->getRatedCount();
        $bbox1 = self::getBoundingBox($settings->fontSizeNormal, $settings->font, $text1);
        $bbox2 = self::getBoundingBox($settings->fontSizeBig, $settings->font, $text2);
        $x = $bbox1->x + $lx + (($rx - $lx - $bbox1->width - $bbox2->width) >> 1);
        $y = $bbox2->y/*ugly but works*/ + (($bbox2->height - $bboxSmall->height) >> 1);
        imagettftext($img, $settings->fontSizeNormal, 0, $x, $y, $settings->colors[self::COLOR_FONT_LIGHT], $settings->font, $text1);
        $x += $bbox1->width + $bbox2->x;
        $y = $bboxBig->y + (($h - $bboxBig->height) >> 1);
        imagettftext($img, $settings->fontSizeBig, 0, $x, $y, $settings->colors[self::COLOR_FONT_DARK], $settings->font, $text2);
        return $img;
    }



    public static function work(&$controllerContext, &$viewContext)
    {
        $ratingDistribution = [];
        foreach (Media::getConstList() as $media)
        {
            $entries = $viewContext->user->getMixedUserMedia($media);
            $entriesNonPlanned = UserMediaFilter::doFilter($entries, [UserMEdiaFilter::nonPlanned()]);
            $ratingDistribution[$media] = RatingDistribution::fromEntries($entries);
        }

        //get input data from GET
        $userSettings = !empty($_GET['settings']) ? json_decode(base64_decode($_GET['settings']), true) : [];
        $imageType = !empty($userSettings[0])
            ? $userSettings[0]
            : null;
        if (!in_array($imageType, [self::IMAGE_TYPE_ANIME, self::IMAGE_TYPE_MANGA, self::IMAGE_TYPE_ANIME_MANGA]))
        {
            $imageType = self::IMAGE_TYPE_ANIME;
        }

        $settings = new StdClass();
        $settings->font = Config::$mediaDirectory . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR . 'OpenSans-Regular.ttf';
        $settings->fontSizeSmall = 7;
        $settings->fontSizeNormal = 9;
        $settings->fontSizeBig = 10.5;
        $settings->barWidth = 220;
        $settings->barHeight = 11;
        $settings->barPadding = 1;
        $settings->colors =
        [
            self::COLOR_BARS1       => '00a4c0f4',
            self::COLOR_BARS2       => '0013459a',
            self::COLOR_BAR_GUIDES1 => 'eea4c0f4',
            self::COLOR_BAR_GUIDES2 => 'ee13459a',
            self::COLOR_BACKGROUND  => 'ffffffff',
            self::COLOR_FONT_DARK   => '00000000',
            self::COLOR_FONT_LIGHT  => 'aa000000',
            self::COLOR_TITLE       => '00577fc2',
            self::COLOR_LOGO        => '00577fc2',
        ];
        foreach (array_keys($settings->colors) as $key)
        {
            if (isset($userSettings[$key]))
            {
                $value = $userSettings[$key];
                assert(in_array(strlen($value), [6, 8]));
                $settings->colors[$key] = $value;
            }
        }

        $settings->colors = array_map(function($color)
        {
            $value = array_map('hexdec', str_split($color, 2));
            if (count($value) == 3)
            {
                array_unshift($value, 0);
            }
            $value[0] >>= 1;
            $c = 0;
            while (!empty($value))
            {
                $c <<= 8;
                $c |= array_shift($value);
            }
            return $c;
        }, $settings->colors);

        $margin = 6;
        $iconImage = self::getIconImage($settings);
        if ($imageType == self::IMAGE_TYPE_ANIME || $imageType == self::IMAGE_TYPE_MANGA)
        {
            $media = $imageType == self::IMAGE_TYPE_ANIME ? Media::Anime : Media::Manga;
            $barsImage = self::getBarsImage($settings, $ratingDistribution[$media], false);
            $barsAxesImage = self::getBarsAxesImage($settings, false);
            $barsBandsImage = self::getBarsBandsImage($settings, $ratingDistribution[$media], false);
            $footerImage = self::getFooterImage($settings, $ratingDistribution[$media], $media, false);
            $w = imagesx($barsImage) + imagesx($barsAxesImage) + imagesx($barsBandsImage);
            $h = imagesy($barsImage) + $margin + imagesy($footerImage);
            $img = imagecreatetruecolor($w, $h);
            imagealphablending($img, false);
            imagefilledrectangle($img, 0, 0, $w, $h, $settings->colors[self::COLOR_BACKGROUND]);
            $x = 0;
            $y = imagesy($barsAxesImage) + $margin;
            self::drawCopy($img, $barsAxesImage, $x, 0); $x += imagesx($barsAxesImage);
            self::drawCopy($img, $barsImage, $x, 0);
            self::drawCopy($img, $footerImage, $x, $y); $x += imagesx($barsImage);
            self::drawCopy($img, $barsBandsImage, $x, 0); $x += imagesx($barsBandsImage);
            $x = (imagesx($barsAxesImage) - imagesx($iconImage)) >> 1;
            self::drawCopy($img, $iconImage, $x, $y - 3);
        }
        else
        {
            $barsImage1 = self::getBarsImage($settings, $ratingDistribution[Media::Anime], true);
            $barsImage2 = self::getBarsImage($settings, $ratingDistribution[Media::Manga], false);
            $barsAxesImage = self::getBarsAxesImage($settings, true);
            $barsBandsImage1 = self::getBarsBandsImage($settings, $ratingDistribution[Media::Anime], true);
            $barsBandsImage2 = self::getBarsBandsImage($settings, $ratingDistribution[Media::Manga], false);
            $footerImage1 = self::getFooterImage($settings, $ratingDistribution[Media::Anime], Media::Anime, true);
            $footerImage2 = self::getFooterImage($settings, $ratingDistribution[Media::Manga], Media::Manga, false);
            $w = imagesx($barsImage1) + imagesx($barsImage2) + imagesx($barsAxesImage) + imagesx($barsBandsImage1) + imagesx($barsBandsImage2);
            $h = imagesy($barsImage1) + $margin + imagesy($footerImage1);
            $img = imagecreatetruecolor($w, $h);
            imagealphablending($img, false);
            imagefilledrectangle($img, 0, 0, $w, $h, $settings->colors[self::COLOR_BACKGROUND]);
            $x = 0;
            $y = imagesy($barsAxesImage) + $margin;
            self::drawCopy($img, $barsBandsImage1, $x, 0); $x += imagesx($barsBandsImage1);
            self::drawCopy($img, $barsImage1, $x, 0);
            self::drawCopy($img, $footerImage1, $x, $y); $x += imagesx($barsImage1);
            self::drawCopy($img, $barsAxesImage, $x, 0); $x += imagesx($barsAxesImage);
            self::drawCopy($img, $barsImage2, $x, 0);
            self::drawCopy($img, $footerImage2, $x, $y); $x += imagesx($barsImage2);
            self::drawCopy($img, $barsBandsImage2, $x, 0);
            $x = imagesx($barsBandsImage1) + imagesx($barsImage1) + ((imagesx($barsAxesImage) - imagesx($iconImage)) >> 1);
            self::drawCopy($img, $iconImage, $x, $y - 3);
        }
        imagesavealpha($img, true);

        $viewContext->layoutName = null;
        HttpHeadersHelper::setCurrentHeader('Cache-Control', 'no-cache, must-revalidate');
        HttpHeadersHelper::setCurrentHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
        imagepng($img);
    }
}
