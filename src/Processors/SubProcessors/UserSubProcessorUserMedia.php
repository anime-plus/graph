<?php
class UserSubProcessorUserMedia extends UserSubProcessor
{
	const URL_ANIMELIST = 0;
	const URL_MANGALIST = 1;
	const URL_ANIMEINFO = 2;
	const URL_MANGAINFO = 3;

	public function getURLs($userName)
	{
		return
		[
			self::URL_ANIMELIST => 'https://myanimelist.net/animelist/' . $userName . '?status=8',
			self::URL_MANGALIST => 'https://myanimelist.net/mangalist/' . $userName . '?status=8',
			self::URL_ANIMEINFO => 'https://myanimelist.net/malappinfo.php?u=' . $userName . '&status=all&type=anime',
			self::URL_MANGAINFO => 'https://myanimelist.net/malappinfo.php?u=' . $userName . '&status=all&type=manga',
		];
	}

	public function process(array $documents, &$context)
	{
		Database::delete('usermedia', ['user_id' => $context->user->id]);

		$context->user->cool = false;
        
        foreach (Media::getConstList() as $media) {
            $key = $media === Media::Anime ? self::URL_ANIMELIST : self::URL_MANGALIST;
            
            $isPrivate = $documents[$key]->content === '403';
            
			$key = $media == Media::Anime
				? self::URL_ANIMEINFO
				: self::URL_MANGAINFO;
			$doc = $documents[$key];
			$dom = self::getDOM($doc);
			$xpath = new DOMXPath($dom);
			if ($xpath->query('//myinfo')->length == 0)
				throw new BadProcessorDocumentException($doc, 'myinfo block is missing');
			if (strpos($doc->content, '</myanimelist>') === false)
				throw new BadProcessorDocumentException($doc, 'list is only partially downloaded');

			$nodes = $xpath->query('//anime | //manga');
			$data = [];
			foreach ($nodes as $root)
			{
				$mediaMalId = Strings::makeInteger(self::getNodeValue($xpath, 'series_animedb_id | series_mangadb_id', $root));
				$score      = Strings::makeInteger(self::getNodeValue($xpath, 'my_score', $root));
				$startDate  = Strings::makeDate(self::getNodeValue($xpath, 'my_start_date', $root));
				$finishDate = Strings::makeDate(self::getNodeValue($xpath, 'my_finish_date', $root));
				$status     = Strings::makeEnum(self::getNodeValue($xpath, 'my_status', $root), [
					1 => UserListStatus::Completing,
					2 => UserListStatus::Finished,
					3 => UserListStatus::OnHold,
					4 => UserListStatus::Dropped,
					6 => UserListStatus::Planned
				], UserListStatus::Unknown);

				$finishedEpisodes = null;
				$finishedChapters = null;
				$finishedVolumes = null;
				switch ($media)
				{
					case Media::Anime:
						$finishedEpisodes = Strings::makeInteger(self::getNodeValue($xpath, 'my_watched_episodes', $root));
						break;
					case Media::Manga:
						$finishedChapters = Strings::makeInteger(self::getNodeValue($xpath, 'my_read_chapters', $root));
						$finishedVolumes  = Strings::makeInteger(self::getNodeValue($xpath, 'my_read_volumes', $root));
						break;
					default:
						throw new BadMediaException();
				}

				$data [] = [
					'user_id' => $context->user->id,
					'mal_id' => $mediaMalId,
					'media' => $media,
					'score' => $score,
					'start_date' => $startDate,
					'end_date' => $finishDate,
					'finished_episodes' => $finishedEpisodes,
					'finished_chapters' => $finishedChapters,
					'finished_volumes' => $finishedVolumes,
					'status' => $status,
				];
			}
			Database::insert('usermedia', $data);

			$dist = RatingDistribution::fromEntries(ReflectionHelper::arraysToClasses($data));

			$daysSpent = Strings::makeFloat(self::getNodeValue($xpath, '//user_days_spent_watching'));
			$user = &$context->user;
			$user->{Media::toString($media) . '_days_spent'} = $daysSpent;
			$user->{Media::toString($media) . '_private'} = $isPrivate;
			$user->cool |= ($dist->getRatedCount() >= 50 and $dist->getStandardDeviation() >= 1.5);
			R::store($user);
		}
	}
}
