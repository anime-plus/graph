<?php
require_once __DIR__ . '/../src/core.php';

function processQueue($queue, $count, $maxAttempts, $logger, $callback)
{
	$processed = 0;
	while ($processed < $count)
	{
		$queueItem = $queue->peek();
		if ($queueItem === null)
			break;

		$key = $queueItem->item;
		$errors = false;

		try
		{
			$callback($key);
		}
		catch (BadProcessorKeyException $e)
		{
			$logger->log('error: ' . $e->getMessage());
		}
		catch (DocumentException $e)
		{
			$logger->log('error: ' . $e->getMessage());
			$errors = true;
		}
		catch (DownloadFailureException $e)
		{
			$logger->log('error: ' . $e->getMessage());
			$errors = true;
		}
		catch (Exception $e)
		{
			$logger->log('error');
			$logger->log($e);
			$errors = true;
		}

		if (!$errors)
		{
			$queue->dequeue();
		}
		else
		{
            $queue->dequeue();

            $enqueueAtStart = $queueItem->attempts < $maxAttempts;

            if ($enqueueAtStart) {
                $queueItem->attempts ++;

                $queue->enqueue($queueItem, $enqueueAtStart);
            }
		}

		++ $processed;
	}
}

CronRunner::run(__FILE__, function($logger)
{
	$userProcessor = new UserProcessor();
    $userMediaProcessor = new UserMediaProcessor();
	$mediaProcessors =
	[
		Media::Anime => new AnimeProcessor(),
		Media::Manga => new MangaProcessor()
	];

	$userQueue = new Queue(Config::$userQueuePath);
    $userMediaQueue = new Queue(Config::$userMediaQueuePath);
    $mediaQueue = new Queue(Config::$mediaQueuePath);

    if ($userQueue->size() < Config::$usersPerCronRun)
    {
        Config::$mediaPerCronRun = floor(Config::$mediaPerCronRun / Config::$usersPerCronRun * (Config::$usersPerCronRun - $userQueue->size()));
    }
    else
    {
        Config::$mediaPerCronRun = 0;
    }

	Downloader::setLogger($logger);

	#process users
	processQueue(
		$userQueue,
		Config::$usersPerCronRun,
		Config::$userQueueMaxAttempts,
		$logger,
		function($userName) use ($userProcessor, $mediaQueue, $logger)
		{
			Database::selectUser($userName);
			$logger->log('Processing user %s... ', $userName);

			#process the user
			$userContext = $userProcessor->process($userName);

			#remove associated cache
			$cache = new Cache();
			$cache->setPrefix($userName);
			foreach ($cache->getAllFiles() as $path)
				unlink($path);

			#append media to queue
			$mediaIds = [];
			foreach (Media::getConstList() as $media)
			{
				foreach ($userContext->user->getMixedUserMedia($media) as $entry)
				{
					$mediaAge = time() - strtotime($entry->processed);
					if ($mediaAge > Config::$mediaQueueMinWait)
						$mediaIds []= TextHelper::serializeMediaId($entry);
				}
			}

			$mediaQueue->enqueueMultiple(array_map(function($mediaId)
				{
					return new QueueItem($mediaId);
				}, $mediaIds));

			$logger->log('ok');
		});

	#process users media
	processQueue(
		$userMediaQueue,
		Config::$usersPerCronRun,
		Config::$userQueueMaxAttempts,
		$logger,
		function($userName) use ($userMediaProcessor, $mediaQueue, $logger)
		{
			Database::selectUser($userName);
			$logger->log('Processing user %s... ', $userName);

			#process the user
			$userContext = $userMediaProcessor->process($userName);

			#remove associated cache
			$cache = new Cache();
			$cache->setPrefix($userName);
			foreach ($cache->getAllFiles() as $path)
				unlink($path);

			#append media to queue
			$mediaIds = [];
			foreach (Media::getConstList() as $media)
			{
				foreach ($userContext->user->getMixedUserMedia($media) as $entry)
				{
					$mediaAge = time() - strtotime($entry->processed);
					if ($mediaAge > Config::$mediaQueueMinWait)
						$mediaIds []= TextHelper::serializeMediaId($entry);
				}
			}

			$mediaQueue->enqueueMultiple(array_map(function($mediaId)
				{
					return new QueueItem($mediaId);
				}, $mediaIds));

			$logger->log('ok');
		});

	$mediaIds = [];
	foreach (Media::getConstList() as $media)
	{
		$entries = Model_Media::getOldest($media, 100);
		foreach ($entries as $entry)
		{
			$mediaAge = time() - strtotime($entry->processed);
			if ($mediaAge > Config::$mediaQueueMinWait)
				$mediaIds []= TextHelper::serializeMediaId($entry);
		}
	}
	$mediaQueue->enqueueMultiple(array_map(function($mediaId)
		{
			return new QueueItem($mediaId);
		}, $mediaIds));



	#process media
	processQueue(
		$mediaQueue,
		Config::$mediaPerCronRun,
		Config::$mediaQueueMaxAttempts,
		$logger,
		function($key) use ($mediaProcessors, $logger)
		{
			list ($media, $malId) = TextHelper::deserializeMediaId($key);
			$logger->log('Processing %s #%d... ', Media::toString($media), $malId);

			#process the media
			$mediaProcessors[$media]->process($malId);

			$logger->log('ok');
		});
});
