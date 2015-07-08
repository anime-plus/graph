<?php
require_once __DIR__ . '/../src/core.php';

$mediaProcessors =
[
	Media::Anime => new AnimeProcessor(),
	Media::Manga => new MangaProcessor()
];

$mediaIds = [];
foreach (Database::getAllDbNames() as $dbName)
{
	Database::attachDatabase($dbName);
	$query = 'SELECT um.mal_id, um.media FROM usermedia um' .
		' GROUP BY um.media, um.mal_id' .
		' HAVING NOT EXISTS(' .
			'SELECT null FROM media m' .
			' WHERE m.mal_id = um.mal_id AND m.media = um.media' .
		') ORDER BY um.mal_id';
	$localMediaIds = array_map(function($row)
		{
			$row =ReflectionHelper::arrayToClass($row);
			return TextHelper::serializeMediaId($row);
		},
		R::getAll($query));
	$mediaIds = array_merge($mediaIds, $localMediaIds);
}
$mediaIds = array_unique($mediaIds);

$pad = strlen(count($mediaIds));
$done = 0;
$exitCode = 0;
foreach ($mediaIds as $mediaId)
{
	try
	{
		++ $done;
		list($media, $malId) = TextHelper::deserializeMediaId($mediaId);
		printf("(%0{$pad}d/%d) Processing %s #%d" . PHP_EOL,
			$done, count($mediaIds),
			Media::toString($media), $malId);

		$mediaProcessors[$media]->process($malId);
	}
	catch (Exception $e)
	{
		echo $e->getMessage() . PHP_EOL;
		$exitCode = 1;
	}
}
exit($exitCode);
