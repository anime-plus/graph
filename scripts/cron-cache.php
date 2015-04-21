<?php
require_once __DIR__ . '/../src/core.php';

CronRunner::run(__FILE__, function($logger)
{
	$cache = new Cache();
	$allFiles = $cache->getAllFiles();
	$usedFiles = $cache->getUsedFiles();
	$unusedFiles = array_diff($allFiles, $usedFiles);
	foreach ($unusedFiles as $path)
	{
		unlink($path);
	}
	$logger->log('Deleted: %s, left: %d', count($unusedFiles), count($usedFiles));
});
