<?php
class UserMediaFilter
{
	public static function doFilter($entries, $filters)
	{
		if (empty($filters))
		{
			return $entries;
		}
		foreach ((array) $filters as $filter)
		{
			$entries = array_filter($entries, $filter);
		}
		return $entries;
	}

	public static function nonPlanned()
	{
		return function($row)
		{
			return $row->status != UserListStatus::Planned;
		};
	}

	public static function dropped()
	{
		return function($row)
		{
			return $row->status == UserListStatus::Dropped;
		};
	}

	public static function finished()
	{
		return function($row)
		{
			return $row->status == UserListStatus::Finished;
		};
	}

	public static function score($score)
	{
		$score = intval($score);
		return function($row) use ($score)
		{
			return intval($row->score) == $score;
		};
	}

	public static function combine()
	{
		return func_get_args();
	}

	public static function lengthGroup($group)
	{
		return function($row) use ($group)
		{
			return MediaLengthDistribution::getGroup($row) == $group;
		};
	}

	public static function publishedYear($year)
	{
		return function($row) use ($year)
		{
			return MediaYearDistribution::getPublishedYear($row) == $year;
		};
	}

	public static function publishedDecade($decade)
	{
		return function($row) use ($decade)
		{
			return MediaDecadeDistribution::getPublishedDecade($row) == $decade;
		};
	}

	public static function nonMovie()
	{
		return function($row)
		{
			return !($row->sub_type == AnimeMediaType::Movie and $row->media == Media::Anime);
		};
	}

	public static function type($type)
	{
		return function($row) use ($type)
		{
			return $row->sub_type == $type;
		};
	}

	public static function creator($creatorIds, $list)
	{
		if (empty($list))
		{
			return [];
		}
		if (!is_array($creatorIds))
		{
			$creatorIds = [$creatorIds];
		}
		$media = reset($list)->media;
		$tblName = Model_MixedUserMedia::createTemporaryTable($list);
		switch ($media)
		{
			case Media::Anime:
				$table = 'animeproducer';
				break;
			case Media::Manga:
				$table = 'mangaauthor';
				break;
			default:
				throw new BadMediaException();
		}
		$query = 'SELECT * FROM ' . $table . ' mc INNER JOIN ' . $tblName . ' ON mc.media_id = ' . $tblName . '.media_id WHERE mc.mal_id IN (' . R::genSlots($creatorIds) . ')';
		$data = R::getAll($query, $creatorIds);
		Model_MixedUserMedia::dropTemporaryTable($tblName);

		$data = array_map(function($x) { return $x['media_id']; }, $data);
		$data = array_flip($data);
		return function($row) use ($data)
		{
			return isset($data[$row->media_id]);
		};
	}


	public static function genre($genreIds, $list)
	{
		if (empty($list))
		{
			return [];
		}
		if (!is_array($genreIds))
		{
			$genreIds = [$genreIds];
		}
		$tblName = Model_MixedUserMedia::createTemporaryTable($list);
		$query = 'SELECT * FROM mediagenre mg INNER JOIN ' . $tblName . ' ON mg.media_id = ' . $tblName . '.media_id WHERE mg.mal_id IN (' . R::genSlots($genreIds) . ')';
		$data = R::getAll($query, $genreIds);
		Model_MixedUserMedia::dropTemporaryTable($tblName);

		$data = array_map(function($x) { return $x['media_id']; }, $data);
		$data = array_flip($data);
		return function($row) use ($data)
		{
			return isset($data[$row->media_id]);
		};
	}

	public static function givenMedia($mediaList)
	{
		return function($e) use ($mediaList)
		{
			return in_array($e->media . $e->mal_id, $mediaList);
		};
	}
}
