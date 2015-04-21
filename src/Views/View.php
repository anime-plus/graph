<?php
class View
{
	protected static $viewContext;

	public static function render($viewContext)
	{
		ob_start();
		$ret = null;
		try
		{
			self::$viewContext = $viewContext;
			self::renderFile($viewContext->layoutName, $viewContext);
			$ret = ob_get_contents();
		}
		finally
		{
			ob_end_clean();
		}
		echo $ret;
	}

	public static function renderView()
	{
		assert(!empty(self::$viewContext->viewName));
		self::renderFile(self::$viewContext->viewName, self::$viewContext);
	}

	public static function renderFile($name, $viewContext)
	{
		if (empty($name))
		{
			return;
		}
		$path = __DIR__ . DIRECTORY_SEPARATOR . $name . '.phtml';

		ob_start();
		try
		{
			include $path;
			$output = ob_get_contents();
		}
		finally
		{
			ob_end_clean();
		}

		if (HttpHeadersHelper::getCurrentHeader('Content-Type') != 'text/html' && HttpHeadersHelper::getCurrentHeader('Content-Type') != 'text/html;charset=UTF-8')
		{
			echo $output;
			return;
		}

		$output = str_replace(' />', '/>', $output);
		$output = str_replace(' >', '>', $output);
		$output = preg_replace_callback('/<[^>]+>/', function($m)
		{
			return str_replace("\t", ' ', $m[0]);
		}, $output);
		$output = str_replace(["\t", "\r", "\n"], '', $output);
		$i = strpos($output, '  ');
		while ($i !== false)
		{
			$output = substr_replace($output, '', $i, 1);
			$i = strpos($output, '  ', $i);
		}
		echo $output;
	}
}
