<?php
class MediaSubProcessorGenres extends MediaSubProcessor
{
	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_MEDIA];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		Database::delete('mediagenre', ['media_id' => $context->media->id]);
		$data = [];
		foreach ($xpath->query('//span[starts-with(text(), \'Genres\')]/../a') as $node)
		{
			preg_match('/=([0-9]+)/', $node->getAttribute('href'), $matches);
			$genreMalId = Strings::makeInteger($matches[1]);
			$genreName = Strings::removeSpaces($node->textContent);
			$data []= [
				'media_id' => $context->media->id,
				'mal_id' => $genreMalId,
				'name' => $genreName
			];
		}
		Database::insert('mediagenre', $data);
	}
}
