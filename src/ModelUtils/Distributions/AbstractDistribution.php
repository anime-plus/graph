<?php
abstract class AbstractDistribution
{
	const IGNORE_NULL_KEY = 1;
	const IGNORE_EMPTY_GROUPS = 2;

	protected $groups = [];
	protected $entries = [];
	protected $keys = [];

	/**
	* Public constructors
	*/

	protected function __construct()
	{
	}

	public static function fromArray(array $arrayDist)
	{
		$dist = new static();
		foreach ($arrayDist as $key => $count)
		{
			$dist->groups[$key] = intval($count);
			$dist->entries[$key] = [];
			$dist->keys[$key] = $key;
		}
		$dist->finalize();
		return $dist;
	}

	public static function fromEntries(array $entries = [])
	{
		$dist = new static();
		foreach ($entries as $entry)
		{
			$dist->addEntry($entry);
		}
		$dist->finalize();
		return $dist;
	}

	protected abstract function addEntry($entry);

	/**
	* AbstractDistribution construction
	*/

	protected function finalize()
	{
	}

	private static function serializeKey($key)
	{
		if (is_object($key))
		{
			if (!empty($key->mal_id))
				return $key->mal_id;
			return serialize($key);
		}
		return (string) $key;
	}

	protected function addGroup($key)
	{
		$safeKey = self::serializeKey($key);
		if (!isset($this->keys[$safeKey]))
		{
			$this->groups[$safeKey] = 0;
			$this->entries[$safeKey] = [];
			$this->keys[$safeKey] = $key;
		}
	}

	public function addToGroup($key, $entry, $weight = 1)
	{
		$this->addGroup($key);
		$safeKey = self::serializeKey($key);
		$this->groups[$safeKey] += $weight;
		$this->entries[$safeKey] []= $entry;
	}

	public function getGroupEntries($key)
	{
		$safeKey = self::serializeKey($key);
		if (!isset($this->entries[$safeKey]))
		{
			return null;
		}
		return $this->entries[$safeKey];
	}

	public function getGroupSize($key)
	{
		$safeKey = self::serializeKey($key);
		if (!isset($this->groups[$safeKey]))
		{
			return null;
		}
		return $this->groups[$safeKey];
	}



	public function getGroupsKeys($flags = 0)
	{
		$x = [];
		foreach (array_keys($this->groups) as $k)
		{
			$x[$k] = $this->keys[$k];
		}
		if ($flags & self::IGNORE_NULL_KEY)
		{
			unset($x[$this->getNullGroupKey()]);
		}
		if ($flags & self::IGNORE_EMPTY_GROUPS)
		{
			$x = array_filter($x, function($key)
			{
				return $this->getGroupSize($key) > 0;
			});
		}
		return $x;
	}

	public function getGroupsEntries($flags = 0)
	{
		$keys = $this->getGroupsKeys($flags);
		$x = [];
		foreach ($keys as $key)
		{
			$safeKey = self::serializeKey($key);
			$x[$safeKey] = $this->getGroupEntries($key);
		}
		return $x;
	}

	public function getAllEntries($flags = 0)
	{
		$groups = self::getGroupsEntries($flags);
		if ($groups === null)
		{
			return null;
		}
		$x = [];
		foreach ($groups as $key => $entries)
		{
			foreach ($entries as $entry)
			{
				$x[$entry->id] = $entry;
			}
		}
		return $x;
	}

	public function getGroupsSizes($flags = 0)
	{
		$keys = $this->getGroupsKeys($flags);
		$x = [];
		foreach ($keys as $key)
		{
			$safeKey = self::serializeKey($key);
			$x[$safeKey] = $this->getGroupSize($key);
		}
		return $x;
	}

	public function getLargestGroupSize($flags = 0)
	{
		$x = $this->getGroupsSizes($flags);
		if (empty($x))
		{
			return 0;
		}
		return max($x);
	}

	public function getLargestGroupKey($flags = 0)
	{
		return $this->keys[array_search($this->getLargestGroupSize($flags), $this->groups)];
	}

	public function getSmallestGroupSize($flags = 0)
	{
		$x = $this->getGroupsSizes($flags);
		return min($x);
	}

	public function getSmallestGroupKey($flags = 0)
	{
		return $this->keys[array_search($this->getSmallestGroupSize($flags), $this->groups)];
	}

	public function getTotalSize($flags = 0)
	{
		return array_sum($this->getGroupsSizes($flags));
	}
}
