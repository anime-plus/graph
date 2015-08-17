<?php
class MediaSubProcessorBasic extends MediaSubProcessor
{
    public function process(array $documents, &$context)
    {
        $document = $documents[self::URL_MEDIA];
        
        $dom = self::getDOM($document);
        
        $xpath = new DOMXPath($dom);
        
        if ($xpath->query('//div[@class = \'badresult\']')->length >= 1) {
            throw new BadProcessorKeyException($context->key);
        }
        
        $title = Strings::removeSpaces(self::getNodeValue($xpath, '//h1//span[@itemprop = \'name\']'));
        
        if (empty($title)) {
            throw new BadProcessorDocumentException($document, 'empty title');
        }
        
        $typeMal = strtolower(Strings::removeSpaces(self::getNodeValue($xpath, '//span[starts-with(text(), \'Type\')]/following-sibling::node()[self::text()]')));
        
        $type = Strings::makeEnum(
            $typeMal,
            [
                'tv' => AnimeMediaType::TV,
                'ova' => AnimeMediaType::OVA,
                'movie' => AnimeMediaType::Movie,
                'special' => AnimeMediaType::Special,
                'ona' => AnimeMediaType::ONA,
                'music' => AnimeMediaType::Music,
                'manga' => MangaMediaType::Manga,
                'novel' => MangaMediaType::Novel,
                'one-shot' => MangaMediaType::Oneshot,
                'doujinshi' => MangaMediaType::Doujinshi,
                'manhwa' => MangaMediaType::Manhwa,
                'manhua' => MangaMediaType::Manhua,
                'oel' => MangaMediaType::OEL,
                '' => $this->media == Media::Manga ? MangaMediaType::Unknown : AnimeMediaType::Unknown
            ],
            null
        );
        
        if ($type === null) {
            throw new BadProcessorDocumentException($document, 'empty sub type');
        }
        
        $image = self::getNodeValue($xpath, '//td[@class = \'borderClass\']//img', null, 'src');
        
        $score = Strings::makeFloat(self::getNodeValue($xpath, '//span[@itemprop = \'ratingValue\']'));
        
        $scoredByUsers = Strings::extractInteger(self::getNodeValue($xpath, '//span[@itemprop = \'ratingCount\']'));
        
        $ranked = Strings::makeInteger(self::getNodeValue($xpath, '//span[starts-with(text(), \'Ranked\')]/following-sibling::node()[self::text()]'));
        
        $popularity = Strings::makeInteger(self::getNodeValue($xpath, '//span[starts-with(text(), \'Popularity\')]/following-sibling::node()[self::text()]'));
        
        $members = Strings::makeInteger(self::getNodeValue($xpath, '//span[starts-with(text(), \'Members\')]/following-sibling::node()[self::text()]'));
        
        $favorites = Strings::makeInteger(self::getNodeValue($xpath, '//span[starts-with(text(), \'Favorites\')]/following-sibling::node()[self::text()]'));
        
        $statusMal = strtolower(Strings::removeSpaces(self::getNodeValue($xpath, '//span[starts-with(text(), \'Status\')]/following-sibling::node()[self::text()]')));
        
        $status = Strings::makeEnum(
            $statusMal,
            [
                'not yet published' => MediaStatus::NotYetPublished,
                'not yet aired' => MediaStatus::NotYetPublished,
                'publishing' => MediaStatus::Publishing,
                'currently airing' => MediaStatus::Publishing,
                'finished' => MediaStatus::Finished,
                'finished airing' => MediaStatus::Finished
            ],
            null
        );
        
        if ($status === null) {
            throw new BadProcessorDocumentException($document, 'unknown status: ' . $malStatus);
        }
        
        $publishedString = Strings::removeSpaces(self::getNodeValue($xpath, '//span[starts-with(text(), \'Aired\') or starts-with(text(), \'Published\')]/following-sibling::node()[self::text()]'));
        
        $position = strrpos($publishedString, ' to ');
        
        if ($position !== false) {
            $publishedFrom = Strings::makeDate(substr($publishedString, 0, $position));
            
            $publishedTo = Strings::makeDate(substr($publishedString, $position + 4));
        } else {
            $publishedFrom = Strings::makeDate($publishedString);
            
            $publishedTo = Strings::makeDate($publishedString);
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
