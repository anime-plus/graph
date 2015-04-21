<?php
class AdminControllerLoginModule extends AbstractControllerModule
{
	public static function getUrlParts()
	{
		return ['a/login'];
	}

	public static function url()
	{
		return '/a/login';
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		$viewContext->viewName = 'admin-login';
		$viewContext->meta->title = 'Admin &#8212; ' . Config::$title;
		WebMediaHelper::addCustom($viewContext);

		if (isset($_POST['password']))
		{
			$viewContext->entered = $_POST['password'];
			$_SESSION['logged-in'] = $_POST['password'] == Config::$adminPassword;
			if ($_SESSION['logged-in'])
			{
				$url = AdminControllerIndexModule::url();
				$viewContext->viewName = null;
				HttpHeadersHelper::setCurrentHeader('Location', $url);
			}
		}
	}
}
