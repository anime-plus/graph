<?php
interface ISingleton
{
	static function doInit();
}

abstract class Singleton implements ISingleton
{
	static $initialized = [];

    private static function isInitialized()
	{
		$initialized = in_array(static::class, static::$initialized, true);

        if (!$initialized)
        {
            static::$initialized[] = static::class;
        }

		return $initialized;
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
