<?php
require_once __DIR__ . '/../src/core.php';

$mediaQueue = new Queue(Config::$mediaQueuePath);

$query = 'SELECT id, media, mal_id FROM media';
$media = R::getAll($query);

$mediaIds = [];
foreach ($media as $entry)
{
	try
	{
		$mediaIds []= TextHelper::serializeMediaId($entry);
	}
	catch (InvalidArgumentException $e)
	{
		echo 'Media ' . print_r($entry) . ' cannot be serialized';
	}
}

$mediaQueue->enqueueMultiple(array_map(function($mediaId)
	{
		return new QueueItem($mediaId);
	}, $mediaIds));
