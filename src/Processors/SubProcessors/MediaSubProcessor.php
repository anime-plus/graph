<?php
abstract class MediaSubProcessor extends AbstractSubProcessor
{
	const URL_MEDIA = 0;
	const URL_RECS = 1;
	protected $media;

	public function __construct($media)
	{
		$this->media = $media;
	}

	public function getURLs($id)
	{
		$infix = Media::toString($this->media);
		if ($infix === null)
			throw new BadMediaException();

		return
		[
			self::URL_MEDIA => 'http://myanimelist.net/' . $infix . '/' . $id,
			self::URL_RECS => 'http://myanimelist.net/' . $infix . '/' . $id . '/dummy/userrecs',
		];
	}
}
