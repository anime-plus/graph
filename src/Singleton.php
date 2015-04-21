<?php
interface ISingleton
{
	static function doInit();
}

abstract class Singleton implements ISingleton
{
	private static function isInitialized()
	{
		static $initialized = false;
		$ret = $initialized;
		$initialized = true;
		return $ret;
	}

	public static function init()
	{
		if (!static::isInitialized())
		{
			static::doInit();
		}
	}

	private function __construct()
	{
	}
}
