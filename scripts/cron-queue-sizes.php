<?php
require_once __DIR__ . '/../src/core.php';

CronRunner::run(__FILE__, function($logger)
{
	$limit = 2*24*60/5;
	$queueSizes = TextHelper::loadJson(Config::$userQueueSizesPath, true);
	$userQueue = new Queue(Config::$userQueuePath);
	$mediaQueue = new Queue(Config::$mediaQueuePath);

	$key = date('c');
	$queueSizes[$key] = [$userQueue->size(), $mediaQueue->size()];
	ksort($queueSizes, SORT_NATURAL | SORT_FLAG_CASE);
	while (count($queueSizes) > $limit)
	{
		array_shift($queueSizes);
	}

	TextHelper::putJson(Config::$userQueueSizesPath, $queueSizes);
});
