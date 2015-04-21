<?php
abstract class AbstractController
{
	public static function parseRequest($url, &$controllerContext)
	{
		throw new UnimplementedException();
	}

	public static function preWork(&$controllerContext, &$viewContext)
	{
		$module = $controllerContext->module;
		$module::preWork($controllerContext, $viewContext);
	}

	public static function postWork(&$controllerContext, &$viewContext)
	{
		$module = $controllerContext->module;
		$module::postWork($controllerContext, $viewContext);
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		throw new UnimplementedException();
	}

	protected static function getAvailableModulesRegex()
	{
		$urlParts = [];
		foreach (static::getAvailableModules() as $className)
		{
			$urlParts = array_merge($urlParts, $className::getUrlParts());
		}
		$modulesRegex = implode('|', array_map(function($urlPart)
		{
			if (empty($urlPart))
			{
				return '';
			}
			return '/' . $urlPart;
		}, $urlParts));
		return $modulesRegex;
	}

	protected static function getModuleByUrlPart($urlPart)
	{
		foreach (self::getAvailableModules() as $module)
		{
			if (in_array($urlPart, $module::getUrlParts()))
			{
				return $module;
			}
		}
		return null;
	}

	/**
	* Create a mapping of controller modules to their respectable controllers
	*/
	private static $modules = [];
	public static function init()
	{
		$dir = implode(DIRECTORY_SEPARATOR, ['src', 'ControllerModules']);
		$classNames = ReflectionHelper::loadClasses($dir);
		foreach ($classNames as $className)
		{
			$pos = strpos($className, 'Controller');
			$controllerClassName = substr($className, 0, $pos + 10);
			if (!isset(self::$modules[$controllerClassName]))
			{
				self::$modules[$controllerClassName] = [];
			}
			self::$modules[$controllerClassName] []= $className;
		}
		foreach (self::$modules as $controllerClassName => &$classNames)
		{
			uasort($classNames, function($a, $b)
			{
				return $a::getOrder() - $b::getOrder();
			});
		}
	}

	/**
	* Returns list of available modules within derived class
	*/
	public static function getAvailableModules()
	{
		$self = get_called_class();
		if (empty(self::$modules[$self]))
		{
			return [];
		}
		return self::$modules[$self];
	}
}

AbstractController::init();
