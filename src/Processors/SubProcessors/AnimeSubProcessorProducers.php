<?php
class AnimeSubProcessorProducers extends MediaSubProcessor
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
        
        Database::delete(
            'animeproducer',
            [
                'media_id' => $context->media->id
            ]
        );
        
        $data = [];
        
        foreach ($xpath->query('//span[text() = \'Studios:\']/../a') as $node) {
            if (!preg_match('#/producer/([0-9]+)$#', $node->getAttribute('href'), $matches)) {
                continue;
            }
            
            $producerIdMal = Strings::makeInteger($matches[1]);
            
            $producerName = Strings::removeSpaces($node->textContent);
            
            $data[] = [
                'media_id' => $context->media->id,
                'mal_id' => $producerIdMal,
                'name' => $producerName,
            ];
        }
        
        Database::insert('animeproducer', $data);
    }
}
