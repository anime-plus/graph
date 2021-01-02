<?php
class MediaSubProcessorBasic extends MediaSubProcessor
{
    public function process(array $documents, &$context)
    {
        $document = $documents[self::URL_MEDIA];

        $dom = self::getDOM($document);

        $xpath = new DOMXPath($dom);

        if ($xpath->query('//div[@class = \'error404\']')->length >= 1) {
            throw new BadProcessorKeyException($context->key);
        }

        $title = self::getNodeValue($xpath, '//meta[@property = \'og:title\']', null, 'content');

        if ($title === null) {
            throw new BadProcessorDocumentException($document, 'empty title');
        }

        $title = Strings::removeSpaces($title);

        if ($title === '') {
            throw new BadProcessorDocumentException($document, 'empty title');
        }

        $type = strtolower(Strings::removeSpaces(self::getNodeValue($xpath, '//span[text() = \'Type:\']/../a')));

        if ($type === '') {
            $type = strtolower(Strings::removeSpaces(self::getNodeValue($xpath, '//span[text() = \'Type:\']/following-sibling::node()[self::text()]')));
        }

        $typeUnknown = $this->media === Media::Manga ? MangaMediaType::Unknown : AnimeMediaType::Unknown;

        $type = Strings::makeEnum($type, [
            'tv' => AnimeMediaType::TV,
            'ova' => AnimeMediaType::OVA,
            'movie' => AnimeMediaType::Movie,
            'special' => AnimeMediaType::Special,
            'ona' => AnimeMediaType::ONA,
            'music' => AnimeMediaType::Music,
            'manga' => MangaMediaType::Manga,
            'novel' => MangaMediaType::Novel,
            'light novel' => MangaMediaType::LightNovel,
            'one-shot' => MangaMediaType::Oneshot,
            'doujinshi' => MangaMediaType::Doujinshi,
            'manhwa' => MangaMediaType::Manhwa,
            'manhua' => MangaMediaType::Manhua
        ], $typeUnknown);

        $image = self::getNodeValue($xpath, '//meta[@property = \'og:image\']', null, 'content');

        $score = Strings::makeFloat(self::getNodeValue($xpath, '//span[@itemprop = \'ratingValue\']'));

        if (!$score) {
            $score = Strings::makeFloat(self::getNodeValue($xpath, '//span[text() = \'Score:\']/following-sibling::node()[self::text()]'));
        }

        $scoredByUsers = Strings::makeInteger(self::getNodeValue($xpath, '//span[@itemprop = \'ratingCount\']'));

        if (!$scoredByUsers) {
            $scoredByUsers = Strings::extractInteger(self::getNodeValue($xpath, '//small[starts-with(text(), \'(scored by\')]'));
        }

        $ranked = Strings::makeInteger(self::getNodeValue($xpath, '//span[text() = \'Ranked:\']/following-sibling::node()[self::text()]'));

        $popularity = Strings::makeInteger(self::getNodeValue($xpath, '//span[text() = \'Popularity:\']/following-sibling::node()[self::text()]'));

        $members = Strings::makeInteger(self::getNodeValue($xpath, '//span[text() = \'Members:\']/following-sibling::node()[self::text()]'));

        $favorites = Strings::makeInteger(self::getNodeValue($xpath, '//span[text() = \'Favorites:\']/following-sibling::node()[self::text()]'));

        $status = strtolower(Strings::removeSpaces(self::getNodeValue($xpath, '//span[text() = \'Status:\']/following-sibling::node()[self::text()]')));

        $status = Strings::makeEnum($status, [
            'not yet published' => MediaStatus::NotYetPublished,
            'not yet aired' => MediaStatus::NotYetPublished,
            'publishing' => MediaStatus::Publishing,
            'currently airing' => MediaStatus::Publishing,
            'on hiatus' => MediaStatus::Hiatus,
            'discontinued' => MediaStatus::Discontinued,
            'finished' => MediaStatus::Finished,
            'finished airing' => MediaStatus::Finished
        ], null);

        if ($status === null) {
            throw new BadProcessorDocumentException($document, 'unknown status: ' . $status);
        }

        $published = Strings::removeSpaces(self::getNodeValue($xpath, '//span[text() = \'Aired:\' or text() = \'Published:\']/following-sibling::node()[self::text()]'));

        $position = strrpos($published, ' to ');

        if ($position !== false) {
            $publishedFrom = Strings::makeDate(substr($published, 0, $position));

            $publishedTo = Strings::makeDate(substr($published, $position + 4));
        } else {
            $publishedFrom = Strings::makeDate($published);

            $publishedTo = Strings::makeDate($published);
        }

        $media = &$context->media;

        $media->media = $this->media;
        $media->title = $title;
        $media->sub_type = $type;
        $media->picture_url = $image;
        $media->average_score = $score;
        $media->average_score_users = $scoredByUsers;
        $media->publishing_status = $status;
        $media->popularity = $popularity;
        $media->members = $members;
        $media->favorites = $favorites;
        $media->ranking = $ranked;
        $media->published_from = $publishedFrom;
        $media->published_to = $publishedTo;
        $media->processed = date('Y-m-d H:i:s');

        R::store($media);
    }
}
