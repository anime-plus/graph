<?php
class DocumentException extends Exception
{
	public function __construct(Document $document, $message)
	{
		$this->document = $document;
		parent::__construct($message);
	}

	public function getDocument()
	{
		return $this->document;
	}
}
