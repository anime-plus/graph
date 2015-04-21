<?php
class Media extends Enum
{
	const Anime = 'A';
	const Manga = 'M';

	public static function toString($media)
	{
		switch ($media)
		{
			case self::Anime:
				return 'anime';
			case self::Manga:
				return 'manga';
			default:
				return 'unknown';
		}
	}
}
