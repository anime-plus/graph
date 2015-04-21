<?php
class Model_MixedUserMedia
{
	public function __construct(array $columns)
	{
		foreach ($columns as $key => $value)
		{
			$this->$key = $value;
		}

		if ($this->media == Media::Manga)
		{
			$this->duration = 10;
		}

		if (isset($this->finished_episodes) and $this->media == Media::Anime)
		{
			$this->finished_duration = $this->duration * $this->finished_episodes;
		}
		elseif (isset($this->finished_chapters) and $this->media == Media::Manga)
		{
			$this->finished_duration = $this->duration * $this->finished_chapters;
		}

		if (empty($this->title))
		{
			$this->title = 'Unknown ' . Media::toString($this->media) . ' entry #' . $this->mal_id;
		}

		$this->mal_link = 'http://myanimelist.net/' . Media::toString($this->media) . '/' . $this->mal_id;
	}

	public function getSeason()
	{
		$monthMap = [
			1 => 'winter',
			2 => 'winter',
			3 => 'spring',
			4 => 'spring',
			5 => 'spring',
			6 => 'summer',
			7 => 'summer',
			8 => 'summer',
			9 => 'fall',
			10 => 'fall',
			11 => 'fall',
			12 => 'winter',
		];

		$yearA = intval(substr($this->published_from, 0, 4));
		$yearB = intval(substr($this->published_to, 0, 4));
		$monthA = intval(substr($this->published_from, 5, 2));
		$monthB = intval(substr($this->published_to, 5, 2));
		if (!$yearA and !$yearB)
		{
			return null;
		}
		elseif (!$yearA)
		{
			if ($monthB)
			{
				return $monthMap[$monthB] . ' ' . $yearB;
			}
			return strval($yearB);
		}
		if ($monthA)
		{
			return $monthMap[$monthA] . ' ' . $yearA;
		}
		return strval($yearA);
	}


	public static function getFromIdList($list)
	{
		$allEntries = [];
		foreach (array_chunk($list, Config::$maxDbBindings) as $chunk)
		{
			$query = 'SELECT m.*, m.id AS media_id FROM media m WHERE m.media || m.mal_id IN (' . R::genSlots($chunk) . ')';
			$rows = R::getAll($query, $chunk);
			$entries = array_map(function($entry) { return new Model_MixedUserMedia($entry); }, $rows);
			$allEntries = array_merge($allEntries, $entries);
		}
		return $allEntries;
	}

	public static function getRatingDistribution($media, $doRecompute = false)
	{
		$query = 'SELECT score, COUNT(score) AS count FROM usermedia WHERE media = ? GROUP BY score';
		$result = R::getAll($query, [$media]);
		$dist[$media] = [];
		foreach ($result as $row)
		{
			$count = $row['count'];
			$score = $row['score'];
			$dist[$media][$score] = $count;
		}
		return RatingDistribution::fromArray($dist[$media]);
	}

	/**
	* Map entries to dictionary of franchise->entries
	*/
	private static function clusterize($entries)
	{
		$clusters = [];
		foreach ($entries as $entry)
		{
			if (!isset($clusters[$entry->franchise]))
			{
				$clusters[$entry->franchise] = [];
			}
			$clusters[$entry->franchise] []= $entry;
		}
		return $clusters;
	}

	public static function getFranchises(array $ownEntries, $loadEverything = false)
	{
		$ownClusters = self::clusterize($ownEntries);

		if ($loadEverything)
		{
			$tblName = 'hurr';
			$query = 'CREATE TEMPORARY TABLE ' . $tblName . ' (franchise VARCHAR(10))';
			R::exec($query);
			foreach (array_chunk(array_keys($ownClusters), Config::$maxDbBindings) as $chunk)
			{
				$query = 'INSERT INTO ' . $tblName . ' VALUES ' . join(',', array_fill(0, count($chunk), '(?)'));
				R::exec($query, $chunk);
			}
			$query = 'SELECT * FROM media INNER JOIN ' . $tblName . ' ON media.franchise = ' . $tblName . '.franchise';
			$rows = R::getAll($query);
			$query = 'DROP TABLE ' . $tblName;
			R::exec($query);

			$allEntries = array_map(function($entry) { return new Model_MixedUserMedia($entry); }, $rows);
			$allClusters = self::clusterize($allEntries);
		}

		$franchises = [];
		foreach ($ownClusters as $key => $ownCluster)
		{
			$franchise = new StdClass;
			$franchise->allEntries =
				!empty($allClusters[$key])
				? $allClusters[$key]
				: [];
			$franchise->ownEntries = array_values($ownCluster);
			$franchises []= $franchise;
		}
		return $franchises;
	}

	public static function attachGenres(array &$entries)
	{
		$tblName = self::createTemporaryTable($entries);
		$query = 'SELECT * FROM mediagenre mg INNER JOIN ' . $tblName . ' ON mg.media_id = ' . $tblName . '.media_id';
		$rows = R::getAll($query);
		self::dropTemporaryTable($tblName);

		$data = ReflectionHelper::arraysToClasses($rows);
		$map = [];
		foreach ($entries as $entry)
		{
			$entry->genres = [];
			$map[$entry->media_id] = $entry;
		}

		foreach ($data as $row)
		{
			if (!isset($map[$row->media_id]))
			{
				continue;
			}
			if (BanHelper::isGenreBanned($map[$row->media_id]->media, $row->mal_id))
			{
				continue;
			}
			$map[$row->media_id]->genres []= $row;
		}
	}

	public static function attachCreators(array &$entries)
	{
		$tblName = self::createTemporaryTable($entries);
		switch (reset($entries)->media)
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
		$query = 'SELECT * FROM ' . $table . ' mc INNER JOIN ' . $tblName . ' ON mc.media_id = ' . $tblName . '.media_id';
		$rows = R::getAll($query);
		self::dropTemporaryTable($tblName);

		$data = ReflectionHelper::arraysToClasses($rows);
		$map = [];
		foreach ($entries as $entry)
		{
			$entry->creators = [];
			$map[$entry->media_id] = $entry;
		}

		foreach ($data as $row)
		{
			if (!isset($map[$row->media_id]))
			{
				continue;
			}
			if (BanHelper::isCreatorBanned($map[$row->media_id]->media, $row->mal_id))
			{
				continue;
			}
			$map[$row->media_id]->creators []= $row;
		}
	}

	public static function attachRecommendations(array &$entries)
	{
		$tblName = self::createTemporaryTable($entries);
		$query = 'SELECT * FROM mediarec mr INNER JOIN ' . $tblName . ' ON mr.media_id = ' . $tblName . '.media_id';
		$rows = R::getAll($query);
		self::dropTemporaryTable($tblName);

		$map = [];
		foreach ($entries as $entry)
		{
			$entry->recommendations = [];
			$map[$entry->media_id] = $entry;
		}

		foreach ($rows as $row)
		{
			$map[$row['media_id']]->recommendations []= $row;
		}
	}

	private static $temporaryTables = [];
	public static function createTemporaryTable(array $entries)
	{
		$ids = array_map(function($entry) { return $entry->media_id; }, $entries);
		$uniqueId = md5(join(',', $ids));
		$tblName = 'hurr_' . $uniqueId;

		if (!isset(self::$temporaryTables[$tblName]))
		{
			self::$temporaryTables[$tblName] = true;
			$query = 'CREATE TEMPORARY TABLE ' . $tblName . ' (media_id INTEGER)';
			R::exec($query);
			foreach (array_chunk($ids, Config::$maxDbBindings) as $chunk)
			{
				$query = 'INSERT INTO ' . $tblName . ' VALUES ' . join(',', array_fill(0, count($chunk), '(?)'));
				R::exec($query, $chunk);
			}
		}

		return $tblName;
	}

	public static function dropTemporaryTable($tblName)
	{
		#$query = 'DROP TABLE ' . $tblName;
		#R::exec($query);
	}
}
