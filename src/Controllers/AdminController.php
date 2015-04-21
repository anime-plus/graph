<?php
class AdminController extends AbstractController
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
		$controllerContext->cache->bypass(true);
		assert(!empty($controllerContext->module));
		return true;
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		session_start();
		if (!isset($_SESSION['logged-in']))
		{
			$controllerContext->module = 'AdminControllerLoginModule';
		}

		HttpHeadersHelper::setCurrentHeader('Content-Type', 'text/html');
		assert(!empty($controllerContext->module));
		$module = $controllerContext->module;
		$module::work($controllerContext, $viewContext);
	}
}
