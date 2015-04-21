<?php
class BadProcessorDocumentException extends DocumentException
{
	public function __construct(Document $document, $message)
	{
		parent::__construct($document, 'Bad document (' . $message . ') in ' . $document->url);
	}
}
