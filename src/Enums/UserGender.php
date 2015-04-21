<?php
class UserGender extends Enum
{
	const Male = 'M';
	const Female = 'F';
	const Unknown = '?';

	public static function toString($gender)
	{
		switch ($gender)
		{
			case self::Male:
				return 'male';
			case self::Female:
				return 'female';
			default:
				return 'unknown';
		}
	}
}
