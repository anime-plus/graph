<?php
abstract class Enum
{
	public static function getConstList()
	{
		return (new ReflectionClass(get_called_class()))->getConstants();
	}
}
