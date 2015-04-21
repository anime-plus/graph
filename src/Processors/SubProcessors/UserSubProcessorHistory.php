<?php
class UserSubProcessorHistory extends UserSubProcessor
{
	const URL_HISTORY = 0;

	public function getURLs($userName)
	{
		return
		[
			self::URL_HISTORY => 'http://myanimelist.net/history/' . $userName,
		];
	}

	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_HISTORY];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		Database::delete('userhistory', ['user_id' => $context->user->id]);

		$data = [];
		$nodes = $xpath->query('//table//td[@class = \'borderClass\']/..');
		foreach ($nodes as $node)
		{
			//basic info
			$link = $node->childNodes->item(0)->childNodes->item(0)->getAttribute('href');
			preg_match('/(\d+)\/?$/', $link, $matches);
			$media = strpos($link, 'manga') !== false ? Media::Manga : Media::Anime;
			$mediaMalId = intval($matches[0]);
			$progress = Strings::makeInteger($node->childNodes->item(0)->childNodes->item(2)->nodeValue);

			//parse time
			//That's what MAL servers output for MG client
			if (isset($doc->headers['Date']))
			{
				date_default_timezone_set('UTC');
				$now = strtotime($doc->headers['Date']);
			}
			else
			{
				$now = time();
			}
			date_default_timezone_set('America/Los_Angeles');
			$hour =   date('H', $now);
			$minute = date('i', $now);
			$second = date('s', $now);
			$day =    date('d', $now);
			$month =  date('m', $now);
			$year =   date('Y', $now);
			$dateString = $node->childNodes->item(2)->nodeValue;
			if (preg_match('/(\d*) seconds? ago/', $dateString, $matches))
			{
				$second -= intval($matches[1]);
			}
			elseif (preg_match('/(\d*) minutes? ago/', $dateString, $matches))
			{
				$second -= intval($matches[1]) * 60;
			}
			elseif (preg_match('/(\d*) hours? ago/', $dateString, $matches))
			{
				$minute -= intval($matches[1]) * 60;
			}
			elseif (preg_match('/Today, (\d*):(\d\d) (AM|PM)/', $dateString, $matches))
			{
				$hour = intval($matches[1]);
				$minute = intval($matches[2]);
				$hour += ($matches[3] == 'PM' and $hour != 12) ? 12 : 0;
			}
			elseif (preg_match('/Yesterday, (\d*):(\d\d) (AM|PM)/', $dateString, $matches))
			{
				$hour = intval($matches[1]);
				$minute = intval($matches[2]);
				$hour += ($matches[3] == 'PM' and $hour != 12) ? 12 : 0;
				$hour -= 24;
			}
			elseif (preg_match('/(\d\d)-(\d\d)-(\d\d), (\d*):(\d\d) (AM|PM)/', $dateString, $matches))
			{
				$year = intval($matches[3]) + 2000;
				$month = intval($matches[1]);
				$day = intval($matches[2]);
				$hour = intval($matches[4]);
				$minute = intval($matches[5]);
				$hour += ($matches[6] == 'PM' and $hour != 12) ? 12 : 0;
			}
			$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
			date_default_timezone_set('UTC');

			$data []= [
				'user_id' => $context->user->id,
				'mal_id' => $mediaMalId,
				'media' => $media,
				'progress' => $progress,
				'timestamp' => date('Y-m-d H:i:s', $timestamp)
			];
		}
		Database::insert('userhistory', $data);
	}
}
