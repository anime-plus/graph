<?php
class IndexController extends AbstractController
{
	public static function parseRequest($url, &$controllerContext)
	{
		$modulesRegex = self::getAvailableModulesRegex();
		$regex = '^/?(' . $modulesRegex . ')/?$';
		if (!preg_match('#' . $regex . '#', $url, $matches))
		{
			return false;
		}
		$rawModule = trim($matches[1], '/');
		$controllerContext->rawModule = $rawModule;
		$controllerContext->module = self::getModuleByUrlPart($rawModule);
		assert(!empty($controllerContext->module));
		return true;
	}

	public static function preWork(&$controllerContext, &$viewContext)
	{
		$controllerContext->cache->bypass(true);
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		HttpHeadersHelper::setCurrentHeader('Content-Type', 'text/html');
		assert(!empty($controllerContext->module));
		$module = $controllerContext->module;
		$module::work($controllerContext, $viewContext);
	}
}
