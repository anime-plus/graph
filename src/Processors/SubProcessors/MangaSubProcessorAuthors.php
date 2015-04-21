<?php
class MangaSubProcessorAuthors extends MediaSubProcessor
{
	public function __construct()
	{
		parent::__construct(Media::Manga);
	}

	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_MEDIA];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		Database::delete('mangaauthor', ['media_id' => $context->media->id]);
		$data = [];
		foreach ($xpath->query('//span[starts-with(text(), \'Authors\')]/../a') as $node)
		{
			preg_match('/people\/([0-9]+)\//', $node->getAttribute('href'), $matches);
			$authorMalId = Strings::makeInteger($matches[1]);
			$authorName = Strings::removeSpaces($node->nodeValue);
			$data []= [
				'media_id' => $context->media->id,
				'mal_id' => $authorMalId,
				'name' => $authorName
			];
		}
		Database::insert('mangaauthor', $data);
	}
}
