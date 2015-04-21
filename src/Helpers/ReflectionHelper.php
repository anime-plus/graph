<?php
class ReflectionHelper
{
	public static function loadClasses($dir)
	{
		$oldClassNames = get_declared_classes();
		foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $fileName)
		{
			if (is_file($fileName))
			{
				include $fileName;
			}
		}
		$newClassNames = get_declared_classes();

		$classNames = array_diff($newClassNames, $oldClassNames);
		$classNames = array_filter($classNames, [__CLASS__, 'isConcrete']);
		return $classNames;
	}

	public static function isAbstract($className)
	{
		$class = new ReflectionClass($className);
		return $class->isAbstract();
	}

	public static function isConcrete($className)
	{
		return !self::isAbstract($className);
	}

	public static function arrayToClass(array $array, $className = null)
	{
		$x = $className === null
			? new StdClass
			: new $className;
		foreach ($array as $key => $val)
		{
			$x->$key = $val;
		}
		return $x;
	}

	public static function arraysToClasses(array $array, $className = null)
	{
		$x = [];
		foreach ($array as $key => $subArray)
		{
			$x[$key] = self::arrayToClass($subArray, $className);
		}
		return $x;
	}
}
