<?php
class AnimeMediaType extends MediaType
{
	const Unknown = 0;
	const TV = 1;
	const OVA = 2;
	const Movie = 3;
	const Special = 4;
	const ONA = 5;
	const Music = 6;
    const CM = 7;
    const PV = 8;
    const TV_Special = 9;

	public static function toString($type, $media = null)
	{
		switch ($type)
		{
			case self::TV:
				return 'TV';
			case self::OVA:
				return 'OVA';
			case self::Movie:
				return 'movie';
			case self::Special:
				return 'special';
			case self::ONA:
				return 'ONA';
			case self::Music:
				return 'music';
            case self::CM:
                return 'CM';
            case self::PV:
                return 'PV';
            case self::TV_Special:
                return 'TV special';
			default:
				return 'unknown';
		}
	}
}
