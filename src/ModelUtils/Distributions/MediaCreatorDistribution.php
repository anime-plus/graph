<?php
class MediaCreatorDistribution extends AbstractDistribution
{
	public function getNullGroupKey()
	{
		return 0;
	}

	public static function fromEntries(array $entries = [])
	{
		$dist = new self();
		if (!empty($entries))
		{
			Model_MixedUserMedia::attachCreators($entries);
			foreach ($entries as $entry)
			{
				$dist->addEntry($entry);
			}
		}
		$dist->finalize();
		return $dist;
	}

	protected function addEntry($entry)
	{
		foreach ($entry->creators as $creator)
		{
			$this->addToGroup($creator, $entry);
		}
	}
}
