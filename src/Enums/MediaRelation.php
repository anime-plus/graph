<?php
class MediaRelation extends Enum
{
	const Sequel = 1;
	const Prequel = 2;
	const SideStory = 3;
	const ParentStory = 4;
	const Adaptation = 5;
	const AlternativeVersion = 6;
	const Summary = 7;
	const Character = 8;
	const SpinOff = 9;
	const AlternativeSetting = 10;
	const Other = 11;
	const FullStory = 12;

	public static function toString($type, $media = null)
	{
		switch ($type)
		{
			case self::Sequel:
				return 'sequel';
			case self::Prequel:
				return 'prequel';
			case self::SideStory:
				return 'side story';
			case self::ParentStory:
				return 'parent story';
			case self::Adaptation:
				return 'adaptation';
			case self::AlternativeVersion:
				return 'alt. version';
            case self::Summary:
                return 'summary';
            case self::Character:
                return 'character';
            case self::SpinOff:
                return 'spin-off';
			case self::AlternativeSetting:
				return 'alt. setting';
			case self::Other:
				return 'other';
			case self::FullStory:
				return 'full story';
			default:
				return 'unknown';
		}
	}
}
