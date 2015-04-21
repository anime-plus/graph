<?php
class AnimeSubProcessorBasic extends MediaSubProcessor
{
	public function __construct()
	{
		parent::__construct(Media::Anime);
	}

	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_MEDIA];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		//duration
		preg_match_all('/([0-9]+)/', self::getNodeValue($xpath, '//span[starts-with(text(), \'Duration\')]/following-sibling::node()[self::text()]'), $matches);
		array_reverse($matches);
		$duration = 0;
		foreach($matches[0] as $r)
		{
			$duration *= 60;
			$duration += $r;
		}

		//episode count
		preg_match_all('/([0-9]+|Unknown)/', self::getNodeValue($xpath, '//span[starts-with(text(), \'Episodes\')]/following-sibling::node()[self::text()]'), $matches);
		$episodeCount = Strings::makeInteger($matches[0][0]);

		$media = &$context->media;
		$media->duration = $duration;
		$media->episodes = $episodeCount;
		R::store($media);
	}
}
