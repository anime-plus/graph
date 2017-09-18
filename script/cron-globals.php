<?php
require_once __DIR__ . '/../src/core.php';

CronRunner::run(__FILE__, function($logger)
{
	$userCount = 0;
    $userCountAll = 0;
	$mediaCount = [];
	$distArr = [];
	foreach (Media::getConstList() as $media)
	{
		$distArr[$media] = [];
		$mediaCount[$media] = Model_Media::getCount($media);
	}
	foreach (Database::getAllDbNames() as $dbName)
	{
		$logger->log($dbName);
		Database::attachDatabase($dbName);
		foreach (Media::getConstList() as $media)
		{
			$localDist = Model_MixedUserMedia::getRatingDistribution($media);
			foreach ($localDist->getGroupsKeys() as $key)
			{
				if (!isset($distArr[$media][$key]))
				{
					$distArr[$media][$key] = 0;
				}
				$distArr[$media][$key] += $localDist->getGroupSize($key);
			}
		}
		$userCount += Model_User::getCount();
        $userCountAll += Model_User::getCountAll();
	}

	$globalsCache =
	[
		'user-count' => $userCount,
        'user-count-all' => $userCountAll,
		'media-count' => $mediaCount,
		'rating-dist' => $distArr,
	];
	TextHelper::putJson(Config::$globalsCachePath, $globalsCache);
});
