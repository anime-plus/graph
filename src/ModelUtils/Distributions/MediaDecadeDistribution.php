<?php
class MediaDecadeDistribution extends AbstractDistribution
{
	protected function finalize()
	{
		if (!empty($this->keys))
		{
			$min = $max = null;
			foreach ($this->keys as $safeKey => $key)
			{
				if ($safeKey == $this->getNullGroupKey())
				{
					continue;
				}
				if ($min === null or $safeKey < $min)
				{
					$min = $safeKey;
				}
				if ($max === null or $safeKey > $max)
				{
					$max = $safeKey;
				}
			}
			for ($i = $min + 10; $i < $max; $i += 10)
			{
				$this->addGroup($i);
			}
		}

		krsort($this->groups, SORT_NUMERIC);
		krsort($this->entries, SORT_NUMERIC);
	}

	public function getNullGroupKey()
	{
		return 0;
	}

	public static function getPublishedDecade($entry)
	{
		$year = MediaYearDistribution::getPublishedYear($entry);
		$decade = floor($year / 10) * 10;
		return $decade;
	}

	protected function addEntry($entry)
	{
		$this->addToGroup(self::getPublishedDecade($entry), $entry);
	}
}
