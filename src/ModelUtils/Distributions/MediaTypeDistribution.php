<?php
class MediaTypeDistribution extends AbstractDistribution
{
	public function getNullGroupKey()
	{
		return 0;
	}

	protected function addEntry($entry)
	{
		$this->addToGroup($entry->sub_type, $entry);
	}
}

