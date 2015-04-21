<?php
class BadMediaException extends Exception
{
	public function __construct()
	{
		parent::__construct('Unknown media type!');
	}
}
