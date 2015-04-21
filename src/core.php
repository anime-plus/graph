<?php
class AutoLoader
{
	static $dirs;

	public static function init()
	{
		$directoryIterator = new RecursiveDirectoryIterator(__DIR__);
		$iterator = new RecursiveIteratorIterator($directoryIterator);
		$dirs = [];
		foreach ($iterator as $file)
		{
			if ($file->isDir())
			{
				$dirs []= $file->getRealPath();
			}
		}
		$dirs = array_unique($dirs);
		self::$dirs = $dirs;
	}

	public static function load($className)
	{
		$name = $className . '.php';

		foreach (self::$dirs as $dir)
		{
			$path = $dir . DIRECTORY_SEPARATOR . $name;
			if (file_exists($path))
			{
				include $path;
			}
		}
	}
}

AutoLoader::init();
spl_autoload_register(['AutoLoader', 'load']);

date_default_timezone_set('UTC');
ini_set('memory_limit', '512M');
ErrorHandler::init();
Database::init();

$localCore = __DIR__ . DIRECTORY_SEPARATOR . 'local.php';

if (file_exists($localCore)) {
    include $localCore;
}

BenchmarkHelper::init();
