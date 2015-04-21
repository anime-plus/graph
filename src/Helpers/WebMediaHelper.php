<?php
class WebMediaHelper
{
	public static function addFarbtastic($viewContext)
	{
		$viewContext->meta->scripts []= '/media/js/jquery.farbtastic.js';
	}

	public static function addHighcharts($viewContext)
	{
		$viewContext->meta->scripts []= '/media/js/jquery.highcharts.js';
		$viewContext->meta->scripts []= '/media/js/highcharts-mg.js';
	}

	public static function addTablesorter($viewContext)
	{
		$viewContext->meta->scripts []= '/media/js/jquery.tablesorter.js';
	}

	public static function addMiniSections($viewContext)
	{
		$viewContext->meta->styles []= '/media/css/mini-sections.css';
	}

	public static function addInfobox($viewContext)
	{
		$viewContext->meta->styles []= '/media/css/infobox.css';
	}

	public static function addEntries($viewContext)
	{
		$viewContext->meta->styles []= '/media/css/user/entries.css';
		$viewContext->meta->scripts []= '/media/js/user/entries.js';
	}

	public static function addJquery($viewContext)
	{
		$viewContext->meta->scripts []= 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js';
		$viewContext->meta->scripts []= 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/ui/jquery.ui.position.min.js';
		$viewContext->meta->scripts []= '/media/js/jquery.cookie.js';
	}

	public static function addGlider($viewContext)
	{
		$viewContext->meta->styles []= '/media/css/glider.css';
		$viewContext->meta->scripts []= '/media/js/glider.js';
	}

	public static function addBasic($viewContext)
	{
		self::addJquery($viewContext);
		$viewContext->meta->styles []= 'http://fonts.googleapis.com/css?family=Open+Sans|Ubuntu';
		$viewContext->meta->styles []= '/media/css/core.css';
		$viewContext->meta->styles []= '/media/css/icons.css';
		$viewContext->meta->scripts []= '/media/js/misc.js';
		$viewContext->meta->scripts []= '/media/js/tooltips.js';
		self::addGlider($viewContext);
	}

	public static function addHeader($viewContext)
	{
		array_unshift($viewContext->meta->styles, '/media/css/header.css');
	}

	public static function addHeaderless($viewContext)
	{
		array_unshift($viewContext->meta->styles, '/media/css/headerless.css');
	}

	public static function addCustom($viewContext)
	{
		$baseName = str_replace('-', DIRECTORY_SEPARATOR, $viewContext->viewName);
		$name = 'css' . DIRECTORY_SEPARATOR . $baseName . '.css';
		if (file_exists(Config::$mediaDirectory . DIRECTORY_SEPARATOR . $name))
		{
			$viewContext->meta->styles []= str_replace('//', '/', Config::$mediaUrl . str_replace(DIRECTORY_SEPARATOR, '/', $name));
		}
		$name = 'js' . DIRECTORY_SEPARATOR . $baseName . '.js';
		if (file_exists(Config::$mediaDirectory . DIRECTORY_SEPARATOR . $name))
		{
			$viewContext->meta->scripts []= str_replace('//', '/', Config::$mediaUrl . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $name));
		}
	}

	public static function download()
	{
		$urls = [
			'http://cdn.ucb.org.br/Scripts/tablesorter/jquery.tablesorter.min.js'
				=> join(DIRECTORY_SEPARATOR, [Config::$mediaDirectory, 'js', 'jquery.tablesorter.js']),
			'http://code.highcharts.com/highcharts.js'
				=> join(DIRECTORY_SEPARATOR, [Config::$mediaDirectory, 'js', 'jquery.highcharts.js']),
			'https://raw.github.com/mattfarina/farbtastic/cf1c85ae79af05b6530b53a37b3aace8fa992ca7/src/farbtastic.js'
				=> join(DIRECTORY_SEPARATOR, [Config::$mediaDirectory, 'js', 'jquery.farbtastic.js']),
			'https://raw.github.com/carhartl/jquery-cookie/master/jquery.cookie.js'
				=> join(DIRECTORY_SEPARATOR, [Config::$mediaDirectory, 'js', 'jquery.cookie.js']),
		];
		$results = Downloader::downloadMulti(array_keys($urls));
		foreach ($results as $url => $result)
		{
			$srcContent = $result->content;
			$dstPath = $urls[$url];
			echo md5($srcContent) . ' --> ' . $dstPath . PHP_EOL;
			file_put_contents($dstPath, $srcContent);
		}
	}
}
