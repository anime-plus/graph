<?php
require_once __DIR__ . '/../src/core.php';

$processors = [
	'user' => new UserProcessor(),
	'manga' => new MangaProcessor(),
	'anime' => new AnimeProcessor(),
];

array_shift($argv);
$pkey = array_shift($argv);

if (!isset($processors[$pkey]))
{
	printf('Usage: %s %s KEY1 [KEY2, ...]' . PHP_EOL,
		__FILE__, join('|', array_keys($processors)));

	exit(1);
}
$processor = $processors[$pkey];

$logger = new Logger();
Downloader::setLogger($logger);
$exitCode = 0;
foreach ($argv as $key)
{
	$logger->log('Processing %s %s', $pkey, is_numeric($key) ? '#' . $key : $key);

	try
	{
		if ($pkey === 'user')
			Database::selectUser($key);

		$processor->process($key);
	}
	catch (BadProcessorKeyException $e)
	{
		$logger->log($e->getMessage());
	}
	catch (DocumentException $e)
	{
		$logger->log($e->getMessage());
		$exitCode = 1;
	}
	catch (Exception $e)
	{
		$logger->log($e);
		$exitCode = 1;
	}
}
exit($exitCode);
