<?php
class Downloader
{
	protected static $logger;

	public static function setLogger(Logger $logger)
	{
		self::$logger = $logger;
	}

	private static function prepareHandle($url)
	{
		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $url);
		curl_setopt($handle, CURLOPT_HEADER, 1);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($handle, CURLOPT_ENCODING, '');
		curl_setopt($handle, CURLOPT_COOKIEJAR, Config::$downloaderCookieFilePath);
		curl_setopt($handle, CURLOPT_COOKIEFILE, Config::$downloaderCookieFilePath);
		curl_setopt($handle, CURLOPT_USERAGENT, Config::$downloaderUserAgent);
		curl_setopt($handle, CURLOPT_TIMEOUT_MS, Config::$downloaderMaxTimeout);
		if (Config::$downloaderProxy != '')
			curl_setopt($handle, CURLOPT_PROXY, Config::$downloaderProxy);
		return $handle;
	}

	private static function parseResult($result, $url)
	{
		$pos = strpos($result, "\r\n\r\n");
		$content = substr($result, $pos + 4);
		$headerLines = explode("\r\n", substr($result, 0, $pos));

		preg_match('/\d{3}/', array_shift($headerLines), $matches);
		if (!isset($matches[0]))
			throw new DownloadFailureException($url, 'Server didn\'t return response code');
		$code = intval($matches[0]);
		$headers = HttpHeadersHelper::parseHeaderLines($headerLines);
		return new Document($url, $code, $headers, $content);
	}

	private static function urlToPath($url)
	{
		return Config::$mirrorPath . DIRECTORY_SEPARATOR . rawurlencode($url) . '.dat';
	}

	public static function purgeCache(array $urls)
	{
		foreach ($urls as $url)
		{
			$path = self::urlToPath($url);
			if (file_exists($path))
			{
				unlink($path);
			}
		}
	}

	public static function download($url)
	{
		$downloaded = self::downloadMulti([$url]);
		return array_shift($downloaded);
	}

	public static function downloadMulti(array $urls)
	{
		$handles = [];
		$documents = [];
		$allUrls = array_combine($urls, $urls);

		//if mirror exists, load its content and purge url from download queue
		if (Config::$mirrorEnabled)
		{
			foreach ($allUrls + [] as $url)
			{
				$path = self::urlToPath($url);
				if (file_exists($path))
				{
					if (self::$logger)
						self::$logger->log('Loading from mirror ' . $url);
					$rawResult = file_get_contents($path);
					$documents[$url] = self::parseResult($rawResult, $url);
					unset($allUrls[$url]);
				}
			}
		}

		foreach (array_chunk($allUrls, Config::$downloaderMaxParallelJobs) as $urls)
		{
			if (self::$logger)
			{
				foreach ($urls as $url)
					self::$logger->log('Downloading ' . $url);
			}

			if (Config::$downloaderUseMultiHandles)
			{
				//prepare curl handles
				$multiHandle = curl_multi_init();
				foreach ($urls as $url)
				{
					$handle = self::prepareHandle($url);
					curl_multi_add_handle($multiHandle, $handle);
					$handles[$url] = $handle;
				}

				//run the query
				$running = null;
				do
				{
					$status = curl_multi_exec($multiHandle, $running);
				}
				while ($status == CURLM_CALL_MULTI_PERFORM);
				while ($running and $status == CURLM_OK)
				{
					if (curl_multi_select($multiHandle) != -1)
					{
						do
						{
							$status = curl_multi_exec($multiHandle, $running);
						}
						while ($status == CURLM_CALL_MULTI_PERFORM);
					}
				}

				//get the documents from curl
				foreach ($handles as $url => $handle)
				{
					$rawResult = curl_multi_getcontent($handle);
					if (Config::$mirrorEnabled)
					{
						file_put_contents(self::urlToPath($url), $rawResult);
					}
					$documents[$url] = self::parseResult($rawResult, $url);
					curl_multi_remove_handle($multiHandle, $handle);
				}

				//close curl handles
				curl_multi_close($multiHandle);
			}
			else
			{
				foreach ($urls as $url)
				{
					$handle = self::prepareHandle($url);
					$rawResult = curl_exec($handle);
					curl_close($handle);

					if (Config::$mirrorEnabled)
					{
						file_put_contents(self::urlToPath($url), $rawResult);
					}
					$documents[$url] = self::parseResult($rawResult, $url);
				}
			}
		}

		return $documents;
	}
}
