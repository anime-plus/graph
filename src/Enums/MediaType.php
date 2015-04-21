<?php
class MediaType extends Enum
{
	public static function toString($type, $media)
	{
		switch ($media)
		{
			case Media::Anime:
				return AnimeMediaType::toString($type);
			case Media::Manga:
				return MangaMediaType::toString($type);
			default:
				return 'unknown';
		}
	}
}
