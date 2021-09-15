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

        $media->duration = $duration;
        $media->episodes = $episodes;

        R::store($media);
    }
}
