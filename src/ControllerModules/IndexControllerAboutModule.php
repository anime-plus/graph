<?php
class IndexControllerAboutModule extends AbstractControllerModule
{
	public static function getUrlParts()
	{
		return ['s/about'];
	}

	public static function url()
	{
		return '/s/about';
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		$viewContext->viewName = 'index-about';
		$viewContext->meta->title = 'About - ' . Config::$title;
		WebMediaHelper::addCustom($viewContext);
	}
}
