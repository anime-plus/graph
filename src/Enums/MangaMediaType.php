<?php
class MangaMediaType extends MediaType
{
	const Unknown = 0;
	const Manga = 1;
	const Novel = 2;
	const Oneshot = 3;
	const Doujinshi = 4;
	const Manhwa = 5;
	const Manhua = 6;

	public static function toString($type, $media = null)
	{
		switch ($type)
		{
			case self::Manga:
				return 'manga';
			case self::Novel:
				return 'novel';
			case self::Oneshot:
				return 'one-shot';
			case self::Doujinshi:
				return 'doujinshi';
			case self::Manhwa:
				return 'manhwa';
			case self::Manhua:
				return 'manhua';
			default:
				return 'unknown';
		}
	}
}
