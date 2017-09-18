<?php
class Model_User extends RedBean_SimpleModel
{
	public function getMixedUserMedia($media)
	{
		$query = 'SELECT m.*, um.*, m.id AS media_id FROM usermedia um' .
			' LEFT JOIN media m ON m.media = um.media AND m.mal_id = um.mal_id' .
			' WHERE um.user_id = ?';
		$rows = R::getAll($query, [$this->id]);
		$rows = array_filter($rows, function($row) use ($media) { return $row['media'] == $media; });
		return array_map(function($row) { return new Model_MixedUserMedia($row); }, $rows);
	}

	public function getFriends()
	{
		$query = 'SELECT * FROM userfriend' .
			' WHERE user_id = ?' .
			' ORDER BY name COLLATE NOCASE ASC';
		$rows = R::getAll($query, [$this->id]);
		return ReflectionHelper::arraysToClasses($rows);
	}

	public function getHistory($media)
	{
		$result = [];
		$query = 'SELECT m.*, uh.*, m.id AS media_id FROM userhistory uh' .
			' LEFT JOIN media m ON m.media = uh.media AND m.mal_id = uh.mal_id' .
			' WHERE uh.user_id = ? AND uh.media = ?' .
			' ORDER BY timestamp DESC';
		$rows = R::getAll($query, [$this->id, $media]);
		return array_map(function($row) { return new Model_MixedUserMedia($row); }, $rows);
	}

	public function isUserMediaPrivate($media)
	{
		return $this->{Media::toString($media) . '_private'};
	}

	public static function getCount()
	{
        $query = 'SELECT COUNT(*) AS `count` FROM user WHERE `processed` > ?';
        
        return intval(R::getAll($query, [date('Y-m-d H:i:s', strtotime('-1 month'))])[0]['count']);
	}
    
	public static function getCountAll()
	{
        $query = 'SELECT COUNT(*) AS `count` FROM user';
        
        return intval(R::getAll($query, [])[0]['count']);
	}

	public static function getCoolUsers($goal)
	{
		$query = 'SELECT id FROM user WHERE cool = 1 ORDER BY RANDOM() LIMIT ?';
		$userIds = array_map(function($x) { return intval($x['id']); }, R::getAll($query, [$goal]));
		if (empty($userIds))
		{
			return [];
		}

		$query = 'id IN (' . R::genSlots($userIds) . ')';
		$result = R::findAll('user', $query, $userIds);
		return array_map(function($x) { return $x->box(); }, $result);
	}

	public function getMismatchedUserMedia(array $entries)
	{
		$entriesMismatched = [];
		foreach ($entries as $entry)
		{
			if ($entry->media == Media::Anime)
			{
				$a = $entry->finished_episodes;
				$b = $entry->episodes;
			} else {
				$a = $entry->finished_chapters;
				$b = $entry->chapters;
			}
			if ($a != $b and ($b > 0 or $entry->publishing_status == MediaStatus::Publishing) and $entry->status == UserListStatus::Finished)
			{
				$entriesMismatched []= $entry;
			}
		}
		return $entriesMismatched;
	}
}
