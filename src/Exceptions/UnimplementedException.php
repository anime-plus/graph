<?php
class UnimplementedException extends Exception
{
	public function __construct()
	{
		parent::__construct('Method not implemented.');
	}
}
