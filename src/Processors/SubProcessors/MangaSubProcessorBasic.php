<?php
class MangaSubProcessorBasic extends MediaSubProcessor
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

		//chapter count
		preg_match_all('/([0-9]+|Unknown)/', self::getNodeValue($xpath, '//span[starts-with(text(), \'Chapter\')]/following-sibling::node()[self::text()]'), $matches);
		$chapterCount = Strings::makeInteger($matches[0][0]);

		//volume count
		preg_match_all('/([0-9]+|Unknown)/', self::getNodeValue($xpath, '//span[starts-with(text(), \'Volume\')]/following-sibling::node()[self::text()]'), $matches);
		$volumeCount = Strings::makeInteger($matches[0][0]);

		//serialization
		$serializationMalId = null;
		$serializationName = null;
		$q = $xpath->query('//span[starts-with(text(), \'Serialization\')]/../a');
		if ($q->length > 0)
		{
			$node = $q->item(0);
			preg_match('/=([0-9]+)/', $node->getAttribute('href'), $matches);
			$serializationMalId = Strings::makeInteger($matches[1]);
			$serializationName = Strings::removeSpaces($q->item(0)->nodeValue);
		}

		$media = &$context->media;
		$media->chapters = $chapterCount;
		$media->volumes = $volumeCount;
		$media->serialization_id = $serializationMalId;
		$media->serialization_name = $serializationName;
		R::store($media);
	}
}
