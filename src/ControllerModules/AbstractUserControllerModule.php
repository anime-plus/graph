<?php
abstract class AbstractUserControllerModule extends AbstractControllerModule
{
	/**
	* This method returns the text that is rendered in the menu in layout.
	*/
	public static function getText(ViewContext $viewContext, $media)
	{
		throw new UnimplementedException();
	}

	/**
	* This method returns what sections of menu in layout should this module be
	* rendered in.
	*/
	public static function getMediaAvailability()
	{
		throw new UnimplementedException();
	}

	/**
	* This method constructs the URL that is going to be used in layouts,
	* views, etc.
	*/
	public static function url()
	{
		$args = func_get_args();
		$userName = array_shift($args);
		$media = array_shift($args);

		$urlParts = static::getUrlParts();
		$bestPart = array_shift($urlParts);
		while (empty($bestPart) and !empty($urlParts))
		{
			$bestPart = array_shift($urlParts);
		}
		$url = '/' . $userName;
		$url .= '/' . $bestPart;
		if (!empty(static::getMediaAvailability()))
		{
			$url .= ',' . Media::toString($media);
		}
		return UrlHelper::absoluteUrl($url);
	}

	public static function getContentType()
	{
		return 'text/html';
	}
}
