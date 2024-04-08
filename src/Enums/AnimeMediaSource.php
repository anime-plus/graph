<?php
class AnimeMediaSource extends Enum
{
	const UNKNOWN = 0;
    const ORIGINAL = 1;
	const MANGA = 2;
    const FOUR_KOMA_MANGA = 3;
    const WEB_MANGA = 4;
    const NOVEL = 6;
    const LIGHT_NOVEL = 7;
    const VISUAL_NOVEL = 8;
    const GAME = 9;
    const CARD_GAME = 10;
    const BOOK = 11;
    const PICTURE_BOOK = 12;
    const RADIO = 13;
    const MUSIC = 14;
    const WEB_NOVEL = 15;
    const MIXED_MEDIA = 16;

    public static function toString($source)
	{
		switch ($source)
		{
			case self::ORIGINAL:
				return 'original';
			case self::MANGA:
                return 'manga';
            case self::FOUR_KOMA_MANGA:
                return '4-koma manga';
			case self::WEB_MANGA:
				return 'web manga';
			case self::NOVEL:
				return 'novel';
			case self::LIGHT_NOVEL:
				return 'light novel';
			case self::VISUAL_NOVEL:
				return 'visual novel';
            case self::GAME:
                return 'game';
            case self::CARD_GAME:
                return 'card game';
            case self::BOOK:
                return 'book';
            case self::PICTURE_BOOK:
                return 'picture book';
            case self::RADIO:
                return 'radio';
            case self::MUSIC:
                return 'music';
            case self::WEB_NOVEL:
                return 'web novel';
            case self::MIXED_MEDIA:
                return 'mixed media';
            default:
				return 'unknown';
		}
	}
}
