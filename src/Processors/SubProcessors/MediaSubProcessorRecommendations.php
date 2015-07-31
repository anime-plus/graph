<?php
class MediaSubProcessorRecommendations extends MediaSubProcessor
{
    public function process(array $documents, &$context)
    {
        $document = $documents[self::URL_RECS];
        
        $dom = self::getDOM($document);
        
        $xpath = new DOMXPath($dom);
        
        Database::delete('mediarec', [
            'media_id' => $context->media->id
        ]);
        
        $data = [];
        
        foreach ($xpath->query('//h2[text()[contains(., \'Recommendations\')]]/following-sibling::node()[@class=\'borderClass\']') as $node) {
            preg_match('#/([0-9]+)/#', self::getNodeValue($xpath, './/strong/..', $node, 'href'), $matches);
            
            $idMal = Strings::makeInteger($matches[1]);
            
            $count = 1 + Strings::makeInteger(self::getNodeValue($xpath, './/div[@class=\'spaceit\']//strong', $node));
            
            $data[] = [
                'media_id' => $context->media->id,
                'mal_id' => $idMal,
                'count' => $count
            ];
        }
        
        Database::insert('mediarec', $data);
    }
}
