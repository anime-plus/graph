<?php
class MediaYearDistribution extends AbstractDistribution
{
	protected function finalize()
	{
		/*if (!empty($this->keys))
		{
			$min = $max = reset($this->keys);
			while (list($i,) = each($this->keys))
			{
				if ($min > $i)
				{
					$min = $i;
				}
				elseif ($max < $i)
				{
					$max = $i;
				}
			}
			for ($i = $min + 1; $i < $max; $i ++)
			{
				$this->addGroup($i);
			}
		}*/

		krsort($this->groups, SORT_NUMERIC);
		krsort($this->entries, SORT_NUMERIC);
	}

	public function getNullGroupKey()
	{
		return null;
	}

	public static function getPublishedYear($entry)
	{
		$yearA = intval(substr($entry->published_from, 0, 4));
		$yearB = intval(substr($entry->published_to, 0, 4));
		if (!$yearA and !$yearB)
		{
			return null;
		}
		elseif (!$yearA)
		{
			return $yearB;
		}
		return $yearA;
	}

	public function addEntry($entry)
	{
		$this->addToGroup(self::getPublishedYear($entry), $entry);
	}
}
