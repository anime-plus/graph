<?php
class UserSubProcessorHistory extends UserSubProcessor
{
    const URL_HISTORY = 0;
    
    public function getURLs($username)
    {
        return [
            self::URL_HISTORY => 'https://myanimelist.net/history/' . $username
        ];
    }
    
    public function process(array $documents, &$context)
    {
        $document = $documents[self::URL_HISTORY];
        
        $dom = self::getDOM($document);
        
        $xpath = new DOMXPath($dom);
        
        Database::delete('userhistory', [
            'user_id' => $context->user->id
        ]);
        
        $data = [];
        
        foreach ($xpath->query('//table//td[@class = \'borderClass\']/..') as $node) {
            $link = $node->childNodes->item(0)->childNodes->item(0)->getAttribute('href');
            
            if (!preg_match('#([0-9]+)$#', $link, $matches)) {
                continue;
            }
            
            $media = strpos($link, 'manga') !== false ? Media::Manga : Media::Anime;
            
            $idMal = intval($matches[0]);
            
            $progress = Strings::makeInteger($node->childNodes->item(0)->getElementsByTagName('strong')->item(0)->nodeValue);
            
            if (isset($document->headers['Date'])) {
                date_default_timezone_set('UTC');
                
                $now = strtotime($document->headers['Date']);
            } else {
                $now = time();
            }
            
            date_default_timezone_set('America/Los_Angeles');
            
            $year = date('Y', $now);
            
            $month = date('m', $now);
            
            $day = date('d', $now);
            
            $hour = date('H', $now);
            
            $minute = date('i', $now);
            
            $second = date('s', $now);
            
            $date = $node->childNodes->item(2)->nodeValue;
            
            if (preg_match('#([0-9]+) seconds? ago#i', $date, $matches)) {
                $second -= intval($matches[1]);
            } elseif (preg_match('#([0-9]+) minutes? ago#i', $date, $matches)) {
                $second -= intval($matches[1]) * 60;
            } elseif (preg_match('#([0-9]+) hours? ago#i', $date, $matches)) {
                $minute -= intval($matches[1]) * 60;
            } elseif (preg_match('#Today, ([0-9]{1,2}):([0-9]{2}) (AM|PM)#i', $date, $matches)) {
                $hour = intval($matches[1]);
                
                $minute = intval($matches[2]);
                
                $hour += $matches[3] === 'PM' && $hour !== 12 ? 12 : 0;
            } elseif (preg_match('#Yesterday, ([0-9]{1,2}):([0-9]{2}) (AM|PM)#i', $date, $matches)) {
                $hour = intval($matches[1]);
                
                $minute = intval($matches[2]);
                
                $hour += $matches[3] === 'PM' && $hour !== 12 ? 12 : 0;
                
                $hour -= 24;
            } elseif (preg_match('#([a-z]+) ([0-9]{1,2}), ([0-9]{1,2}):([0-9]{2}) (AM|PM)#i', $date, $matches)) {
                $month = date_parse($matches[1]);
                
                $month = intval($month['month']);
                
                $day = intval($matches[2]);
                
                $hour = intval($matches[3]);
                
                $minute = intval($matches[4]);
                
                $hour += $matches[5] === 'PM' && $hour !== 12 ? 12 : 0;
            } elseif (preg_match('#([a-z]+) ([0-9]{1,2}), ([0-9]{4}) ([0-9]{1,2}):([0-9]{2}) (AM|PM)#i', $date, $matches)) {
                $month = date_parse($matches[1]);
                
                $month = intval($month['month']);
                
                $day = intval($matches[2]);
                
                $year = intval($matches[3]);
                
                $hour = intval($matches[4]);
                
                $minute = intval($matches[5]);
                
                $hour += $matches[6] === 'PM' && $hour !== 12 ? 12 : 0;
            }
            
            $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
            
            date_default_timezone_set('UTC');
            
            $data[] = [
                'user_id' => $context->user->id,
                'mal_id' => $idMal,
                'media' => $media,
                'progress' => $progress,
                'timestamp' => date('Y-m-d H:i:s', $timestamp)
            ];
        }
        
        Database::insert('userhistory', $data);
    }
}
