<?php
class MediaLengthDistribution extends AbstractDistribution
{
	protected function finalize()
	{
		$f = function($a, $b)
		{
			if ($b == '?')
			{
				return -1;
			}
			elseif ($a == '?')
			{
				return 1;
			}
			else
			{
				return intval($a) - intval($b);
			}
		};
		uksort($this->groups, $f);
		uksort($this->entries, $f);
	}

	public function getNullGroupKey()
	{
		return 0;
	}

	public static function getThresholds($media)
	{
		switch ($media)
		{
			case Media::Anime: return [1, 6, 13, 26, 52, 100];
			case Media::Manga: return [1, 10, 25, 50, 100, 200];
		}
		throw new BadMediaException();
	}

	public static function getGroup($entry)
	{
		$thresholds = self::getThresholds($entry->media);
		$thresholds = array_reverse($thresholds);
		$thresholds []= 0;

		switch ($entry->media)
		{
			case Media::Anime: $length = $entry->episodes; break;
			case Media::Manga: $length = $entry->chapters; break;
			default: throw new BadMediaException();
		}
		$group = '?';
		if ($length > 0)
		{
			foreach ($thresholds as $i => $threshold)
			{
				if ($length > $threshold)
				{
					if ($i == 0)
					{
						$group = strval($threshold + 1) . '+';
					}
					else
					{
						$a = $thresholds[$i - 1];
						$b = $threshold + 1;
						if ($a == $b or $threshold == 0)
						{
							$group = strval($a);
						}
						else
						{
							$group = strval($b) . '-' . strval($a);
						}
					}
					break;
				}
			}
		}
		return $group;
	}

	public function addEntry($entry)
	{
		$group = self::getGroup($entry);
		$this->addToGroup($group, $entry);
	}
}
