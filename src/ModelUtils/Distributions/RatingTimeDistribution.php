<?php
class RatingTimeDistribution extends RatingDistribution
{
	public function addEntry($entry)
	{
		$this->addToGroup($entry->score, $entry, $entry->finished_duration);
	}

	public function getTotalTime()
	{
		return $this->getTotalSize();
	}
}
