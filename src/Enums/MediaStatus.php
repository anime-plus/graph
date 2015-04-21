<?php
class MediaStatus extends Enum
{
	const NotYetPublished = 'N';
	const Publishing = 'P';
	const Finished = 'F';

	public static function toString($mediaStatus, $media = null)
	{
		switch ($mediaStatus)
		{
			case self::NotYetPublished:
				switch ($media)
				{
					case Media::Anime: return 'not yet aired';
					default: return 'not yet published';
				}
			case self::Publishing:
				switch ($media)
				{
					case Media::Anime: return 'airing';
					default: return 'publishing';
				}
			case self::Finished:
				switch ($media)
				{
					case Media::Anime: return 'finished airing';
					default: return 'published';
				}
			default:
				return 'unknown';
		}
	}
}
