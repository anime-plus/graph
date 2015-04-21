<?php
class QueueItem
{
	public $item;
	public $attempts;

	public function __construct($item, $attempts = 0)
	{
		$this->item = $item;
		$this->attempts = $attempts;
	}
}
