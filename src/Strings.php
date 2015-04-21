<?php
class Strings
{
	public static function removeSpaces($subject)
	{
		$subject = trim($subject);
		$subject = rtrim($subject, ':');
		while (false !== ($x = strpos($subject, '  ')))
		{
			$subject = str_replace('  ', ' ', $subject);
		}
		$subject = trim($subject);
		return $subject;
	}

	public static function makeInteger($subject)
	{
		$subject = str_replace(',', '', $subject);
		$subject = str_replace('.', '', $subject);
		$subject = str_replace(' ', '', $subject);
		$subject = ltrim($subject, '#');
		$subject = intval($subject);
		return $subject;
	}

	public static function makeFloat($subject)
	{
		$subject = str_replace(' ', '', $subject);
		$subject = floatval($subject);
		return $subject;
	}

	public static function extractInteger($subject)
	{
		preg_match('/\d+/', $subject, $matches);
		if (!$matches)
			return 0;
		return intval($matches[0]);
	}

	public static function makeDate($str)
	{
		$str = trim(str_replace('  ', ' ', $str));
		$monthNames = array_merge
		(
			array_flip([1 => 'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december']),
			array_flip([1 => 'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'])
		);

		$day = null;
		$month = null;
		$year = null;

		$matches = null;
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $str, $matches))
		{
			list(, $year, $month, $day) = $matches;
		}
		elseif (preg_match('/^(\w{3,}) (\d{1,2})(\w*,?) (\d{4})$/', $str, $matches))
		{
			list (, $month, $day, , $year) = $matches;
		}
		elseif (preg_match('/^(\d{1,2})(\w*) (\w{3,}),? (\d{4})$/', $str, $matches))
		{
			list (, $day, , $month, $year) = $matches;
		}
		elseif (preg_match('/^(\d{4}),? (\w{3,}),? (\d{1,2})(\w*)$/', $str, $matches))
		{
			list (, $year, $month, $day) = $matches;
		}
		elseif (preg_match('/^(\w{3,}),? (\d{4})$/', $str, $matches))
		{
			$month = $matches[1];
			$year = $matches[2];
		}
		elseif (preg_match('/^(\d{4})$/', $str, $matches))
		{
			$year = $matches[1];
		}

		if (!($month >= 1 and $month <= 12))
		{
			if (isset($monthNames[strtolower($month)]))
			{
				$month = $monthNames[strtolower($month)];
			}
			else
			{
				$month = null;
			}
		}
		$year = intval($year);
		$day = intval($day);

		$year = $year ?: '????';
		$month = $month ?: '??';
		$day = $day ?: '??';
		return sprintf('%04s-%02s-%02s', $year, $month, $day);
	}

	public static function parseURL($url)
	{
		$parts = parse_url($url);
		if (isset($parts['query']))
		{
			parse_str(urldecode($parts['query']), $parts['query']);
		}
		return $parts;
	}

	public static function makeEnum($source, $table, $default = null)
	{
		foreach ($table as $key => $replacement)
		{
			if ($source == $key)
			{
				return $replacement;
			}
		}
		return $default;
	}
}
