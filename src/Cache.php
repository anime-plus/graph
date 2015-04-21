<?php
class Cache
{
	private $bypassCache;
	private $prefix = null;

	//warning: affects getAllFiles() and getUsedFiles()
	public function setPrefix($prefix)
	{
		$this->prefix = md5(strtolower($prefix));
	}

	public function bypass($doBypass)
	{
		$this->bypassCache = $doBypass;
	}

	public function isBypassed()
	{
		return $this->bypassCache;
	}

	private function urlToPath($url)
	{
		$url = strtolower($url);
		$url = rtrim($url, '/');
		$name = $this->prefix . md5($url) . sha1($url);
		return Config::$cachePath . DIRECTORY_SEPARATOR . $name;
	}

	public function load($url)
	{
		$path = $this->urlToPath($url);
		$data = file_get_contents($path);
		$pos = strpos($data, "\n\n");
		$headers = unserialize(substr($data, 0, $pos));
		$contents = substr($data, $pos + 2);
		foreach ($headers as $key => $value)
		{
			HttpHeadersHelper::setCurrentHeader($key, $value);
		}
		echo $contents;
	}

	public function exists($url)
	{
		$path = $this->urlToPath($url);
		return file_exists($path);
	}

	public function isFresh($url)
	{
		$path = $this->urlToPath($url);
		if (!$this->exists($url))
		{
			return false;
		}
		if ($this->isBypassed())
		{
			return false;
		}
		if (!Config::$cacheEnabled)
		{
			return false;
		}
		if (time() - filemtime($path) > Config::$cacheTimeToLive)
		{
			return false;
		}
		return true;
	}

	public function save($url, $renderFunction)
	{
		$path = $this->urlToPath($url);
		ob_start();

		$renderFunction();
		flush();

		$headers = HttpHeadersHelper::getCurrentHeaders();
		$contents = ob_get_contents();
		ob_end_clean();

		$handle = fopen($path, 'wb');
		flock($handle, LOCK_EX);
		fwrite($handle, serialize($headers));
		fwrite($handle, "\n\n");
		fwrite($handle, $contents);
		fclose($handle);

		echo $contents;
	}

	public function getAllFiles()
	{
		return glob(Config::$cachePath . DIRECTORY_SEPARATOR . $this->prefix . '*');
	}

	public function getUsedFiles()
	{
		$ttl = Config::$cacheTimeToLive;
		$allFiles = $this->getAllFiles();
		$usedFiles = [];
		foreach ($allFiles as $path)
		{
			$age = time() - filemtime($path);
			if ($age <= $ttl)
			{
				$usedFiles []= $path;
			}
		}
		return $usedFiles;
	}
}
