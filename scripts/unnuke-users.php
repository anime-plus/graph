<?php
require_once __DIR__ . '/../src/core.php';

$limit = 500;
$userProcessor = new UserProcessor();

$oldNames = [];
$newNames = [];
foreach (Database::getAllDbNames() as $dbName)
{
	Database::attachDatabase($dbName);
	$query = 'SELECT name FROM userfriend' .
		' GROUP BY name' .
		' ORDER BY RANDOM()' .
		' LIMIT ?';
	$localNewNames = array_map(function($x) { return $x['name']; },
		R::getAll($query, [$limit]));

	$query = 'SELECT name FROM user ORDER BY name LIMIT ?';
	$localOldNames = array_map(function($x) { return $x['name']; },
		R::getAll($query, [$limit]));

	$oldNames = array_merge($oldNames, $localOldNames);
	$newNames = array_merge($newNames, $localNewNames);
}
$newNames = array_diff($newNames, $oldNames);
$newNames = array_slice($newNames, 0, $limit);

$pad = strlen(count($newNames));
$done = 0;
$exitCode = 0;
foreach ($newNames as $name)
{
	try
	{
		++ $done;
		printf("(%0{$pad}d/%d) Processing user %s" . PHP_EOL,
			$done, count($newNames), $name);

		$userProcessor->process($name);
	}
	catch (Exception $e)
	{
		echo $e->getMessage() . PHP_EOL;
		$exitCode = 1;
	}
}
exit($exitCode);
