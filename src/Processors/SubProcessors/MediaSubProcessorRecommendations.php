<?php
class MediaSubProcessorRecommendations extends MediaSubProcessor
{
	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_RECS];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		Database::delete('mediarec', ['media_id' => $context->media->id]);
		$data = [];
		foreach ($xpath->query('//h2[starts-with(text(), \'Recommendations\')]/following-sibling::node()[@class=\'borderClass\']') as $node)
		{
			preg_match('/\/([0-9]+)/', self::getNodeValue($xpath, './/strong/..', $node, 'href'), $matches);
			$recommendedMalId = Strings::makeInteger($matches[1]);
			$recommendationCount = 1 + Strings::makeInteger(self::getNodeValue($xpath, './/div[@class=\'spaceit\']//strong', $node));
			$data []= [
				'media_id' => $context->media->id,
				'mal_id' => $recommendedMalId,
				'count' => $recommendationCount
			];
		}
		Database::insert('mediarec', $data);
	}
}
