<?php
class AnimeSubProcessorBasic extends MediaSubProcessor
{
    public function __construct()
    {
        parent::__construct(Media::Anime);
    }

    public function process(array $documents, &$context)
    {
        $document = $documents[self::URL_MEDIA];

        $dom = self::getDOM($document);

        $xpath = new DOMXPath($dom);

        $source = strtolower(Strings::removeSpaces(self::getNodeValue($xpath, '//span[text() = \'Source:\']/following-sibling::node()[self::text()]')));

        $source = Strings::makeEnum($source, [
            'original' => AnimeMediaSource::ORIGINAL,
            'manga' => AnimeMediaSource::MANGA,
            '4-koma manga' => AnimeMediaSource::FOUR_KOMA_MANGA,
            'web manga' => AnimeMediaSource::WEB_MANGA,
            'novel' => AnimeMediaSource::NOVEL,
            'light novel' => AnimeMediaSource::LIGHT_NOVEL,
            'visual novel' => AnimeMediaSource::VISUAL_NOVEL,
            'game' => AnimeMediaSource::GAME,
            'card game' => AnimeMediaSource::CARD_GAME,
            'book' => AnimeMediaSource::BOOK,
            'picture book' => AnimeMediaSource::PICTURE_BOOK,
            'radio' => AnimeMediaSource::RADIO,
            'music' => AnimeMediaSource::MUSIC,
            'web novel' => AnimeMediaSource::WEB_NOVEL,
            'mixed media' => AnimeMediaSource::MIXED_MEDIA,
        ], AnimeMediaSource::UNKNOWN);

        preg_match_all('#([0-9]+) (hr|min|sec)#', self::getNodeValue($xpath, '//span[text() = \'Duration:\']/following-sibling::node()[self::text()]'), $matches);

        array_reverse($matches);

        $duration = 0;

        foreach ($matches[1] as $key => $value) {
            switch ($matches[2][$key]) {
                case 'hr':
                    $duration += $value * 60;

                    break;
                case 'min':
                    $duration += $value;

                    break;
                case 'sec':
                    $duration++;

                    break;
            }
        }

        preg_match_all('#([0-9]+|Unknown)#', self::getNodeValue($xpath, '//span[text() = \'Episodes:\']/following-sibling::node()[self::text()]'), $matches);

        $episodes = Strings::makeInteger($matches[0][0]);

        $media = &$context->media;

        $media->source = $source;
        $media->duration = $duration;
        $media->episodes = $episodes;

        R::store($media);
    }
}
