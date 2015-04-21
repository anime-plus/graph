<?php
chdir('..');
require_once('src/core.php');

$dir = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'Controllers']);
$classNames = ReflectionHelper::loadClasses($dir);
$classNames = array_filter($classNames, function($className) {
	return substr_compare($className, 'Controller', -10, 10) === 0;
});

$controllerContext = new ControllerContext();
$controllerContext->cache->bypass(!empty($_GET['bypass-cache']));
$viewContext = new ViewContext();
$logger = new Logger(__FILE__);

if (!empty(Config::$maintenanceMessage))
{
	$viewContext->viewName = 'maintenance';
	$viewContext->layoutName = 'layout-headerless';
	View::render($viewContext);
}
elseif (isset($_GET['e']))
{
	try
	{
		$viewContext->viewName = 'error-' . $_GET['e'];
		View::render($viewContext);
	}
	catch (Exception $e)
	{
		$viewContext->viewName = 'error-404';
		View::render($viewContext);
	}
}
else
{
	try
	{
		$url = $_SERVER['REQUEST_URI'];
		if (!empty($_SERVER['HTTP_HOST']) and !empty(Config::$enforcedDomain) and $_SERVER['HTTP_HOST'] != Config::$enforcedDomain)
		{
			$fixedUrl = 'http://' . Config::$enforcedDomain . '/' . trim($_SERVER['REQUEST_URI'], '/');
			HttpHeadersHelper::setCurrentHeader('Location', $fixedUrl);
			exit(0);
		}
		$controllerContext->url = $url;

		$workingClassName = null;
		foreach ($classNames as $className)
		{
			if ($className::parseRequest($url, $controllerContext))
			{
				$workingClassName = $className;
				break;
			}
		}

		if (!empty($workingClassName))
		{
			$workingClassName::preWork($controllerContext, $viewContext);
			if ($controllerContext->cache->isFresh($url))
			{
				$controllerContext->cache->load($url);
				flush();
			}
			else
			{
				$f = function() use ($workingClassName, $controllerContext, $viewContext)
				{
					$workingClassName::work($controllerContext, $viewContext);
					View::render($viewContext);
				};
				if (!$controllerContext->cache->isBypassed())
				{
					$controllerContext->cache->save($url, $f);
				}
				else
				{
					$f();
				}
			}
			$workingClassName::postWork($controllerContext, $viewContext);

			if (HttpHeadersHelper::getCurrentHeader('Content-Type') == 'text/html')
			{
				printf('<!-- retrieved in %.05fs -->', microtime(true) - $viewContext->renderStart);
			}
			exit(0);
		}

		$viewContext->viewName = 'error-404';
		View::render($viewContext);
	}
	catch (Exception $e)
	{
		#log error information
		$logger->log($e);
		$viewContext->viewName = 'error';
		$viewContext->exception = $e;
		View::render($viewContext);
	}
	exit(1);
}
