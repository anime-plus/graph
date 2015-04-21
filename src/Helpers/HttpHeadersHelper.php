<?php
class HttpHeadersHelper
{
	public static function getCurrentHeaders()
	{
		$headerLines = headers_list();
		return self::parseHeaderLines($headerLines);
	}

	public static function parseHeaderLines($headerLines)
	{
		$headers = [];
		foreach ($headerLines as $line)
		{
			list($key, $value) = explode(': ', $line);
			if (!isset($headers[$key]))
			{
				$headers[$key] = $value;
			}
			else
			{
				$headers[$key] = array_merge(
					array($headers[$key]),
					array($value));
			}
		}
		return $headers;
	}

	public static function headersSent()
	{
		return headers_sent();
	}

	public static function getCurrentHeader($givenKey)
	{
		foreach (self::getCurrentHeaders() as $key => $val)
		{
			if (trim(strtolower($givenKey)) == strtolower($key))
			{
				return $val;
			}
		}
		return null;
	}

	public static function setCurrentHeader($key, $value)
	{
		if (isset($_SERVER['HTTP_HOST']))
		{
			header("$key: $value");
		}
	}
}
