<?php
class DistributionEvaluator
{
	public static function evaluate(AbstractDistribution $dist)
	{
		$values = [];
		$allEntries = $dist->getAllEntries();
		$meanScore = 0;
		$rated = 0;
		$unrated = 0;
		foreach ($allEntries as $entry)
		{
			$meanScore += $entry->score;
			if ($entry->score)
			{
				$rated ++;
			}
			else
			{
				$unrated ++;
			}
		}
		$meanScore /= max(1, $rated);
		$largestGroupSize = $dist->getLargestGroupSize();

		foreach ($dist->getGroupsKeys() as $safeKey => $key)
		{
			$entry = [];
			$localMeanScore = 0;
			$localRated = 0;
			$localUnrated = 0;
			foreach ($dist->getGroupEntries($key) as $entry)
			{
				$localMeanScore += $entry->score;
				if ($entry->score)
				{
					$localRated ++;
				}
				else
				{
					$localUnrated ++;
				}
			}
			$localMeanScore = $localMeanScore / max(1, $localRated);
			$localMeanScore = $localMeanScore * $localRated + $meanScore * $localUnrated;
			$localMeanScore /= (float) max(1, $dist->getGroupSize($key));
			$weight = $dist->getGroupSize($key) / max(1, $largestGroupSize);
			$weight = 1 - pow(1 - pow($weight, 8. / 9.), 2);
			$value = $meanScore + ($localMeanScore - $meanScore) * $weight;
			$values[$safeKey] = $value;
		}
		return $values;
	}
}
