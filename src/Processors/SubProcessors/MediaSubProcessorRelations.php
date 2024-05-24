<?php
class MediaSubProcessorRelations extends MediaSubProcessor
{
    public function process(array $documents, &$context)
    {
        $document = $documents[self::URL_MEDIA];

        $dom = self::getDOM($document);

        $xpath = new DOMXPath($dom);

        Database::delete('mediarelation', [
            'media_id' => $context->media->id
        ]);

        $data = [];

        foreach ($xpath->query('//div[@class = \'entries-tile\']//div[@class = \'content\']') as $node) {
            $typeMal = strtolower(Strings::removeSpaces(preg_replace('#\(.+\)#', '', $node->childNodes[1]->textContent)));

            $type = Strings::makeEnum($typeMal, [
                'adaptation' => MediaRelation::Adaptation,
                'alternative setting' => MediaRelation::AlternativeSetting,
                'alternative version' => MediaRelation::AlternativeVersion,
                'character' => MediaRelation::Character,
                'full story' => MediaRelation::FullStory,
                'other' => MediaRelation::Other,
                'parent story' => MediaRelation::ParentStory,
                'prequel' => MediaRelation::Prequel,
                'sequel' => MediaRelation::Sequel,
                'side story' => MediaRelation::SideStory,
                'spin-off' => MediaRelation::SpinOff,
                'summary' => MediaRelation::Summary
            ], null);

            if ($type === null) {
                throw new BadProcessorDocumentException($document, 'unknown relation type: ' . $typeMal);
            }

            $links = $node->getElementsByTagName('a');

            foreach ($links as $link) {
                $link = $link->getAttribute('href');

                if (preg_match('#/(anime|manga)/([0-9]+)/#', $link, $matches)) {
                    $idMal = Strings::makeInteger($matches[2]);

                    if ($matches[1] === 'anime') {
                        $media = Media::Anime;
                    } elseif ($matches[1] === 'manga') {
                        $media = Media::Manga;
                    }

                    $data[] = [
                        'media_id' => $context->media->id,
                        'mal_id' => $idMal,
                        'media' => $media,
                        'type' => $type
                    ];
                }
            }
        }

        foreach ($xpath->query('//table[@class = \'entries-table\']/tr') as $node) {
            $typeMal = strtolower(rtrim(Strings::removeSpaces($node->childNodes[1]->textContent), ':'));

            $type = Strings::makeEnum($typeMal, [
                'adaptation' => MediaRelation::Adaptation,
                'alternative setting' => MediaRelation::AlternativeSetting,
                'alternative version' => MediaRelation::AlternativeVersion,
                'character' => MediaRelation::Character,
                'full story' => MediaRelation::FullStory,
                'other' => MediaRelation::Other,
                'parent story' => MediaRelation::ParentStory,
                'prequel' => MediaRelation::Prequel,
                'sequel' => MediaRelation::Sequel,
                'side story' => MediaRelation::SideStory,
                'spin-off' => MediaRelation::SpinOff,
                'summary' => MediaRelation::Summary
            ], null);

            if ($type === null) {
                throw new BadProcessorDocumentException($document, 'unknown relation type: ' . $typeMal);
            }

            $links = $node->getElementsByTagName('a');

            foreach ($links as $link) {
                $link = $link->getAttribute('href');

                if (preg_match('#/(anime|manga)/([0-9]+)/#', $link, $matches)) {
                    $idMal = Strings::makeInteger($matches[2]);

                    if ($matches[1] === 'anime') {
                        $media = Media::Anime;
                    } elseif ($matches[1] === 'manga') {
                        $media = Media::Manga;
                    }

                    $data[] = [
                        'media_id' => $context->media->id,
                        'mal_id' => $idMal,
                        'media' => $media,
                        'type' => $type
                    ];
                }
            }
        }

        Database::insert('mediarelation', $data);

        $context->relationData = $data;
    }
}
