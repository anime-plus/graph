<?php
class UserSubProcessorHistory extends UserSubProcessor
{
    const URL_ANIME_HISTORY = 0;
    const URL_MANGA_HISTORY = 1;

    public function getURLs($username)
    {
        return [
            self::URL_ANIME_HISTORY => 'https://myanimelist.net/history/' . $username . '/anime',
            self::URL_MANGA_HISTORY => 'https://myanimelist.net/history/' . $username . '/manga',
        ];
    }

    public function process(array $documents, &$context)
    {
        Database::delete('userhistory', [
            'user_id' => $context->user->id,
        ]);

        $data = [];

        foreach ([self::URL_ANIME_HISTORY, self::URL_MANGA_HISTORY] as $k)
        {
            $document = $documents[$k];

            $dom = self::getDOM($document);

            $xpath = new DOMXPath($dom);

            foreach ($xpath->query('//table//td[@class = \'borderClass\']/..') as $node)
            {
                $link = $node->childNodes->item(0)->childNodes->item(0)->getAttribute('href');

                if (!preg_match('#([0-9]+)$#', $link, $matches))
                {
                    continue;
                }

                $media = strpos($link, 'manga') !== false ? Media::Manga : Media::Anime;

                $idMal = intval($matches[0]);

                $progress = Strings::makeInteger($node->childNodes->item(0)->getElementsByTagName('strong')->item(0)->nodeValue);

                $time = time();

                if (isset($document->headers['Date']))
                {
                    date_default_timezone_set('UTC');

                    $time = strtotime($document->headers['Date']);
                }

                date_default_timezone_set('America/Los_Angeles');

                $y = date('Y', $time);

                $m = date('m', $time);

                $d = date('d', $time);

                $h = date('H', $time);

                $i = date('i', $time);

                $s = date('s', $time);

                $date = $node->childNodes->item(1)->nodeValue;

                if (preg_match('#([0-9]+) seconds? ago#i', $date, $matches))
                {
                    $s -= intval($matches[1]);
                }
                elseif (preg_match('#([0-9]+) minutes? ago#i', $date, $matches))
                {
                    $s -= intval($matches[1]) * 60;
                }
                elseif (preg_match('#([0-9]+) hours? ago#i', $date, $matches))
                {
                    $i -= intval($matches[1]) * 60;
                }
                elseif (preg_match('#Today, ([0-9]{1,2}):([0-9]{2}) (AM|PM)#i', $date, $matches))
                {
                    $h = intval($matches[1]);

                    $i = intval($matches[2]);

                    $h += $matches[3] === 'PM' && $h !== 12 ? 12 : 0;
                }
                elseif (preg_match('#Yesterday, ([0-9]{1,2}):([0-9]{2}) (AM|PM)#i', $date, $matches))
                {
                    $h = intval($matches[1]);

                    $i = intval($matches[2]);

                    $h += $matches[3] === 'PM' && $h !== 12 ? 12 : 0;

                    $h -= 24;
                }
                elseif (preg_match('#([a-z]+) ([0-9]{1,2}), ([0-9]{1,2}):([0-9]{2}) (AM|PM)#i', $date, $matches))
                {
                    $m = date_parse($matches[1]);

                    $m = intval($m['month']);

                    $d = intval($matches[2]);

                    $h = intval($matches[3]);

                    $i = intval($matches[4]);

                    $h += $matches[5] === 'PM' && $h !== 12 ? 12 : 0;
                }
                elseif (preg_match('#([a-z]+) ([0-9]{1,2}), ([0-9]{4}) ([0-9]{1,2}):([0-9]{2}) (AM|PM)#i', $date, $matches))
                {
                    $m = date_parse($matches[1]);

                    $m = intval($m['month']);

                    $d = intval($matches[2]);

                    $y = intval($matches[3]);

                    $h = intval($matches[4]);

                    $i = intval($matches[5]);

                    $h += $matches[6] === 'PM' && $h !== 12 ? 12 : 0;
                }

                $timestamp = mktime($h, $i, $s, $m, $d, $y);

                date_default_timezone_set('UTC');

                $data[] = [
                    'user_id' => $context->user->id,
                    'mal_id' => $idMal,
                    'media' => $media,
                    'progress' => $progress,
                    'timestamp' => date('Y-m-d H:i:s', $timestamp),
                ];
            }
        }

        Database::insert('userhistory', $data);
    }
}
