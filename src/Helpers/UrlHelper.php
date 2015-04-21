<?php
class UrlHelper
{
	public static function absoluteUrl($relativeUrl = null, $params = [])
	{
		if ($relativeUrl === null)
		{
			$relativeUrl = '/' . ltrim($_SERVER['REQUEST_URI'], '/');
		}
		if (strpos($relativeUrl, ':') !== false)
		{
			$absoluteUrl = $relativeUrl;
		}
		else
		{
			$absoluteUrl = !empty(Config::$baseUrl)
				? Config::$baseUrl
				: $_SERVER['HTTP_HOST'];
			$absoluteUrl = rtrim($absoluteUrl, '/') . '/';
			$absoluteUrl .= ltrim($relativeUrl, '/');
		}
		if (!empty($params))
		{
			$frag = strpos($absoluteUrl, '?') === false ? '?' : '&';
			$absoluteUrl .= $frag . http_build_query($params);
		}
		$absoluteUrl = preg_replace('/(?<!:)\/\//', '/', $absoluteUrl);
		return $absoluteUrl;
	}

	public static function currentUrl()
	{
		return self::absoluteUrl(null);
	}
}
