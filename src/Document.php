<?php
class Document
{
	public $headers;
	public $content;
	public $url;
	public $code;

	public function __construct($url, $code, $headers, $content)
	{
		$this->url = $url;
		$this->code = $code;
		$this->headers = $headers;
		$this->content = $content;
	}
}
