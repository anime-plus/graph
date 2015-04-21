<?php
class CronRunner
{
	private static $finished = false;

	public static function shutdown($logger)
	{
		if (!self::$finished)
		{
			$logger->log('Finished abnormally');
			mail(Config::$mail, 'Cron crash', 'Cron has crashed. Again.');
			exit(1);
		}
	}

	public static function run($fileName, $callback)
	{
		$logger = new Logger($fileName);

		try
		{
			SingleInstance::run($fileName);
		}
		catch (InstanceAlreadyRunningException $e)
		{
			$logger->log('Already running');
			self::$finished = true;
			exit(1);
		}

		$logger->log('Working');

		register_shutdown_function([get_class(), 'shutdown'], $logger);

		try
		{
			$callback($logger);
		}
		catch (Exception $e)
		{
			$logger->log($e);
			$logger->log('Finished with errors');
			self::$finished = true;
			exit(1);
		}

		$logger->log('Finished');
		self::$finished = true;
		exit(0);
	}
}
