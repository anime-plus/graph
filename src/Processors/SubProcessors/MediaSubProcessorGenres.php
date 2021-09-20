<?php
class MediaSubProcessorGenres extends MediaSubProcessor
{
    public function process(array $documents, &$context)
    {
        $document = $documents[self::URL_MEDIA];

        $dom = self::getDOM($document);

        $xpath = new DOMXPath($dom);

        Database::delete(
            'mediagenre',
            [
                'media_id' => $context->media->id
            ]
        );

        $data = [];

        foreach (['Genre', 'Genres', 'Explicit Genre', 'Explicit Genres', 'Demographic', 'Demographics', 'Theme', 'Themes'] as $text)
        {
            foreach ($xpath->query('//span[text() = \'' . $text . ':\']/../a') as $node) {
                if (!preg_match('#/genre/([0-9]+)/#', $node->getAttribute('href'), $matches)) {
                    continue;
                }

                $genreIdMal = Strings::makeInteger($matches[1]);

                $genreName = Strings::removeSpaces($node->textContent);

                $data[] = [
                    'media_id' => $context->media->id,
                    'mal_id' => $genreIdMal,
                    'name' => $genreName
                ];
            }
        }

        Database::insert('mediagenre', $data);
    }
}
