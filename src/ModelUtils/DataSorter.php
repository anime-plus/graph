<?php
class DataSorter
{
	const MediaMalId = 0;
	const Title = 1;
	const Score = 2;
	const MeanScore = 3;

	public static function sort(&$entries, $sortType)
	{
		switch ($sortType)
		{
			case self::Title:
				$cb = function($a, $b)
				{
					return strnatcasecmp($a->title, $b->title);
				};
				break;
			case self::Score:
				$cb = function($a, $b)
				{
					return $a->score < $b->score ? 1 : -1;
				};
				break;
			case self::MeanScore:
				$cb = function($a, $b)
				{
					return $a->meanScore < $b->meanScore ? 1 : -1;
				};
				break;
			case self::MediaMalId:
				$cb = function($a, $b)
				{
					return strcmp(sprintf('%s%05d', $a->media, $a->mal_id), sprintf('%s%05d', $b->media, $b->mal_id));
				};
				break;
			default:
				throw new RuntimeException('Bad sort type');
		}
		usort($entries, $cb);
	}
}
