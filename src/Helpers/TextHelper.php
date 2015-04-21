<?php
class TextHelper
{
	const TIME_MINUTES = 1;
	const TIME_HOURS = 2;

	public static function loadJson($path, $fetchAsArray = false)
	{
		$contents = file_get_contents($path);
		$contents = preg_replace('/#(.*)$/m', '', $contents);
		return json_decode($contents, $fetchAsArray);
	}

	public static function loadSimpleList($path)
	{
		$contents = file_get_contents($path);
		$contents = preg_replace('/#(.*)$/m', '', $contents);
		$lines = explode("\n", $contents);
		$lines = array_map('trim', $lines);
		$lines = array_filter($lines);
		return $lines;
	}

	public static function putSimpleList($path, $lines)
	{
		$lines = array_map('trim', $lines);
		$lines = array_filter($lines);
		$contents = implode("\n", $lines);
		file_put_contents($path, $contents);
	}

	public static function putJson($path, $json)
	{
		$contents = json_encode($json);
		file_put_contents($path, $contents);
	}

	public static function getTimeText($totalSeconds, $precision)
	{
		$totalMinutes = $totalSeconds / 60.;

		$hours = floor($totalMinutes / 60.);
		$minutes = $totalMinutes % 60;

		$wait = '';
		if ($hours > 0 or $precision == self::TIME_HOURS)
		{
			$wait .= $hours . ' hour';
			if ($hours != 1)
				$wait .= 's';
			$wait .= ' ';
		}

		if ($precision != selF::TIME_HOURS)
		{
			$wait .= $minutes . ' minute';
			if ($minutes != 1)
				$wait .= 's';
		}

		return trim($wait);
	}

	public static function getVolumesText($plural, $short = false)
	{
		return self::getMediaCustomUnitText('vol', 'volume', $plural, $short);
	}

	public static function getMediaUnitText($media, $plural = false, $short = false)
	{
		switch ($media)
		{
			case Media::Anime:
				return self::getMediaCustomUnitText('ep', 'episode', $plural, $short);
			case Media::Manga:
				return self::getMediaCustomUnitText('chap', 'chapter', $plural, $short);
		}
		throw new BadMediaException();
	}

	public static function getNumberedMediaUnitText($media, $number, $short = false)
	{
		$plural = false;
		$prefix = $number;
		if ($prefix == 0)
		{
			$prefix = '?';
			$plural = true;
		}
		elseif ($prefix > 1)
		{
			$plural = true;
		}
		$suffix = self::getMediaUnitText($media, $plural, $short);
		return $prefix . ' ' . $suffix;
	}

	public static function getMediaCustomUnitText($shortForm, $longForm, $plural, $short)
	{
		$text = $short ? $shortForm : $longForm;
		if ($plural)
		{
			$text .= 's';
		}
		return $text;
	}

	public static function replaceTokens($input, array $tokens)
	{
		$output = $input;
		foreach ($tokens as $key => $val)
		{
			if (is_object($val) or is_array($val))
			{
				continue;
			}
			$output = str_replace('{' . $key . '}', $val, $output);
		}
		$output = preg_replace('/\{[\w_-]+\}/', '[unknown]', $output);
		return $output;
	}

	public static function roundPercentages($distribution)
	{
		//largest remainder method
		$total = max(array_sum($distribution), 1);
		$percentages = array_map(function($x) use ($total)
		{
			return $x * 100.0 / $total;
		}, $distribution);

		asort($percentages, SORT_NUMERIC);
		$percentagesRounded = array_map('floor', $percentages);

		$keys = array_keys($percentages);
		$sum = array_sum($percentagesRounded);
		if ($sum == 0)
		{
			return $distribution;
		}
		while ($sum < 100)
		{
			assert(!empty($keys));
			$key = array_shift($keys);
			if ($distribution[$key] != 0)
			{
				$percentagesRounded[$key] ++;
				$sum ++;
			}
		}

		return $percentagesRounded;
	}

	public static function serializeMediaId($entry)
	{
		if (is_object($entry))
		{
			return $entry->media . $entry->mal_id;
		}
		else if (is_array($entry) and isset($entry['media']) and isset($entry['mal_id']))
		{
			return $entry['media'] . $entry['mal_id'];
		}
		else
			throw new InvalidArgumentException();
	}

	public static function deserializeMediaId($id)
	{
		$media = substr($id, 0, 1);
		$malId = intval(substr($id, 1));
		return [$media, $malId];
	}

	public static function roundDecimal($number, $places = 0)
	{
		return self::roundDecimalWithFunction($number, $places, 'round');
	}

	public static function floorDecimal($number, $places = 0)
	{
		return self::roundDecimalWithFunction($number, $places, 'floor');
	}

	public static function ceilDecimal($number, $places = 0)
	{
		return self::roundDecimalWithFunction($number, $places, 'ceil');
	}

	private static function roundDecimalWithFunction($number, $places, $function)
	{
		if ($places >= 0)
		{
			$multiplier = pow(10, $places);
			return sprintf('%.' . $places . 'f', $function($number * $multiplier) / $multiplier);
		}
		else
			throw new InvalidArgumentException();
	}
    
    public static function mailJavaScript($mail)
    {
        $mail = explode('@', $mail);
        
        return '<script>document.write(\'' . implode('\'+\'', str_split($mail[0])) . '\'+\'@\'+\'' . $mail[1] . '\');</script>';
    }
}
