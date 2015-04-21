<?php
class BadProcessorKeyException extends Exception
{
	public function __construct($key)
	{
		parent::__construct('Can\'t process ' . $key . ' - not found on MAL');
	}
}
