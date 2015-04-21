<?php
class IndexControllerSearchModule extends AbstractControllerModule
{
	public static function getUrlParts()
	{
		return ['s/search'];
	}

	public static function url()
	{
		return '/s/search';
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		$userName = !empty($_POST['user-name']) ? $_POST['user-name'] : '';
		$userName = trim($userName);

		if (empty($userName))
		{
			$viewContext->layoutName = null;
			$url = IndexControllerIndexModule::url(rName);
			HttpHeadersHelper::setCurrentHeader('Location', $url);
			return;
		}

		if (!preg_match('#^' . UserController::getUserRegex() . '$#', $userName))
		{
			$viewContext->viewName = 'error-user-invalid';
			return;
		}

		$viewContext->layoutName = null;
		$url = UserControllerProfileModule::url($userName);
		HttpHeadersHelper::setCurrentHeader('Location', $url);
	}
}
