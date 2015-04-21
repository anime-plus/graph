<?php
class SingleInstance
{
	private static $fileHandle = null;

	public static function run($scriptName)
	{
		$fileName = basename($scriptName) . '.lock';
		$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
		self::$fileHandle = fopen($lockFile, 'wb');
		if (!flock(self::$fileHandle, LOCK_EX | LOCK_NB))
		{
			throw new InstanceAlreadyRunningException($scriptName);
		}
	}

	public static function destruct()
	{
		if (self::$fileHandle != null)
		{
			fclose(self::$fileHandle);
		}
	}
}

register_shutdown_function(['SingleInstance', 'destruct']);
