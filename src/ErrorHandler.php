<?php
class ErrorHandler extends Singleton
{
	public static function suppress()
	{
		set_error_handler(function($errno, $errstr, $errfile, $errline) {});
	}

	public static function restore()
	{
		restore_error_handler();
	}

	public static function doInit()
	{
		error_reporting(E_ALL);
		set_error_handler([__CLASS__, 'handler']);
	}

	public static function handler(
		$errorId,
		$errorString,
		$errorFile,
		$errorLine)
	{
		throw new ErrorException(
			$errorString,
			$errorId,
			0,
			$errorFile,
			$errorLine);
	}
}

ErrorHandler::init();
