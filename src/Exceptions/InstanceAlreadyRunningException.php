<?php
class InstanceAlreadyRunningException extends Exception
{
	public function __construct($scriptName)
	{
		parent::__construct('An instance of this script (' . $scriptName . ') is already running!');
	}
}
