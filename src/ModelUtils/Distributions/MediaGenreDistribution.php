<?php
class MediaGenreDistribution extends AbstractDistribution
{
	public function getNullGroupKey()
	{
		return 0;
	}

	public static function fromEntries(array $entries = [])
	{
		$dist = new self();
		Model_MixedUserMedia::attachGenres($entries);
		foreach ($entries as $entry)
		{
			$dist->addEntry($entry);
		}
		$dist->finalize();
		return $dist;
	}

	public function addEntry($entry)
	{
		foreach ($entry->genres as $genre)
		{
			$this->addToGroup($genre, $entry);
		}
	}
}
