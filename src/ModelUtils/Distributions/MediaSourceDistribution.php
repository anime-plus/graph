<?php
class MediaSourceDistribution extends AbstractDistribution
{
	public function getNullGroupKey()
	{
		return 0;
	}

	protected function addEntry($entry)
	{
		$this->addToGroup($entry->source ?? 0, $entry);
	}
}
