<?php
class BenchmarkHelper extends Singleton
{
	private static $start;
	private static $prev;

	public static function benchmark($message = 'ping')
	{
		$now = microtime(true);
		$delta = $now - self::$prev;
		$deltaBig = $now - self::$start;
		self::$prev = $now;

		HttpHeadersHelper::setCurrentHeader('Content-Type', 'text/plain');
		printf('%.05f/%.05f: %s' . PHP_EOL, $delta, $deltaBig, $message);
	}

	public static function doInit()
	{
		self::$prev = self::$start = microtime(true);
	}
}

BenchmarkHelper::init();
