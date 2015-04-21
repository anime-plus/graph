<?php

class Database extends Singleton
{
	public static function doInit()
	{
		include implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'lib', 'redbean', 'RedBean', 'redbean.inc.php']);
		ReflectionHelper::loadClasses(__DIR__ . DIRECTORY_SEPARATOR . 'Models');
		self::loadDatabase('media.sqlite');
	}

	public static function userNameToDbName($userName)
	{
		$names = self::getAllDbNames();
		mt_srand(crc32(strtolower($userName)));
		return $names[mt_rand(0, count($names) - 1)];
	}

	public static function getAllDbNames()
	{
		$ret = [];
		foreach (range(0, Config::$dbCount - 1) as $i)
		{
			$ret []= sprintf('user-%02x.sqlite', $i);
		}
		return $ret;
	}

	public static function selectUser($userName)
	{
		return self::attachDatabase(self::userNameToDbName($userName));
	}

	public static function loadDatabase($dbFile)
	{
		$path = strpos($dbFile, DIRECTORY_SEPARATOR) === false
			? Config::$dbPath . DIRECTORY_SEPARATOR . $dbFile
			: $dbFile;
		$dsn = 'sqlite:' . $path;
		$key = basename($path);
		R::addDatabase($key, $dsn, null, null, true);
		R::selectDatabase($key);
		R::exec('PRAGMA foreign_keys=ON');
		R::exec('PRAGMA temp_store=MEMORY');
	}

	private static $attached = false;
	public static function attachDatabase($dbFile)
	{
		if (self::$attached)
		{
			R::exec('DETACH DATABASE userdb');
		}
		$path = Config::$dbPath . DIRECTORY_SEPARATOR . $dbFile;
		R::exec('ATTACH DATABASE ? AS userdb', [$path]);
		self::$attached = true;
	}

	public static function insert($tableName, $allRows)
	{
		if (empty($allRows))
		{
			return;
		}
		if (!is_array(reset($allRows)))
		{
			$allRows = [$allRows];
		}

		$lastInsertId = null;
		foreach (array_chunk($allRows, Config::$maxDbBindings) as $rows)
		{
			$columns = array_keys(reset($rows));
			$single = '(' . join(', ', array_fill(0, count($columns), '?')) . ')';
			$query = sprintf('INSERT INTO %s(%s) VALUES %s',
				$tableName,
				join(', ', $columns),
				join(', ', array_fill(0, count($rows), $single))
			);
			$flattened = call_user_func_array('array_merge', array_map('array_values', $rows));

			$lastInsertId = R::exec($query, $flattened);
		}
		return $lastInsertId;
	}

	public static function delete($tableName, $conditions)
	{
		$single = [];
		foreach ($conditions as $key => $value)
		{
			$single []= $key . ' = ?';
		}
		$query = sprintf('DELETE FROM %s WHERE %s',
			$tableName,
			join(' AND ', $single));

		R::exec($query, array_values($conditions));
	}
}

Database::init();
