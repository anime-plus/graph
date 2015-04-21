<?php
class UserListStatus extends Enum
{
	const Dropped = 'D';
	const OnHold = 'H';
	const Completing = 'C';
	const Finished = 'F';
	const Planned = 'P';
	const Unknown = '?';

	public static function toString($status, $media = null)
	{
		switch ($status)
		{
			case self::Dropped:
				return 'dropped';
			case self::OnHold:
				return 'on-hold';
			case self::Completing:
				switch ($media)
				{
					case Media::Anime: return 'watching';
					case Media::Manga: return 'reading';
				}
				return 'completing';
			case self::Finished:
				return 'finished';
			case self::Planned:
				return 'planned';
			default:
				return 'unknown';
		}
	}
}
