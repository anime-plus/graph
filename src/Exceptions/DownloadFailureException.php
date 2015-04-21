<?php
class DownloadFailureException extends Exception
{
	public function __construct($url, $reason = null)
	{
		$msg = $reason
			? sprintf('Download failure: %s (%s)', $url, $reason)
			: sprintf('Download failure: %s', $url);
		parent::__construct($msg);
	}
}
