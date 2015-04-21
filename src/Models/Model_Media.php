<?php
class Model_Media extends RedBean_SimpleModel
{
	public static function getCount($media)
	{
		$query = 'SELECT COUNT(*) AS count FROM media WHERE media = ?';
		return intval(R::getAll($query, [$media])[0]['count']);
	}

	public static function getOldest($media, $number)
	{
		return R::find('media', 'media = ? ORDER BY processed ASC LIMIT ?', [$media, $number]);
	}
}
