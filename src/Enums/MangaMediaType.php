<?php
class MangaMediaType extends MediaType
{
	const Unknown = 0;
	const Manga = 1;
	const Novel = 2;
	const OneShot = 3;
	const Doujin = 4;
	const Manhwa = 5;
	const Manhua = 6;
	const OEL = 7;

	public static function toString($type, $media = null)
	{
		switch ($type)
		{
			case self::Manga:
				return 'manga';
			case self::Novel:
				return 'novel';
			case self::OneShot:
				return 'one shot';
			case self::Doujin:
				return 'doujin';
			case self::Manhwa:
				return 'manhwa';
			case self::Manhua:
				return 'manhua';
			case self::OEL:
				return 'OEL';
			default:
				return 'unknown';
		}
	}
}
