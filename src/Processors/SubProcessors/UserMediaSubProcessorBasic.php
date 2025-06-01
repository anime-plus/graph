<?php
class UserMediaSubProcessorBasic extends UserMediaSubProcessor
{
	private $anime = 0;

    private $animeArray = [];

    private $manga = 0;

    private $mangaArray = [];

    public function setAnimeRange($max = 0)
    {
        $this->anime = intval((ceil($max / 300) ?: 1) * 300 - 300);
    }

    public function setMangaRange($max = 0)
    {
        $this->manga = intval((ceil($max / 300) ?: 1) * 300 - 300);
    }

	public function getURLs($name)
	{
        $i = 0;

        foreach (range(0, $this->anime, 300) as $offset)
        {
            $this->animeArray[$i] = 'https://myanimelist.net/animelist/' . $name . '/load.json?status=7&offset=' . $offset;

            $i++;
        }

        foreach (range(0, $this->manga, 300) as $offset)
        {
            $this->mangaArray[$i] = 'https://myanimelist.net/mangalist/' . $name . '/load.json?status=7&offset=' . $offset;

            $i++;
        }

        return array_merge($this->animeArray, $this->mangaArray);
	}

	public function process(array $documents, &$context)
	{
		Database::delete('usermedia', ['user_id' => $context->user->id]);

        $year = date('y', strtotime('+26 hour'));

        foreach (Media::getConstList() as $media) {
			$data = [];

            $range = $media === Media::Anime ? array_keys($this->animeArray) : array_keys($this->mangaArray);

            foreach ($range as $offset)
            {
                $nodes = json_decode(str_replace('<?xml encoding="utf-8"?>', '', $documents[$offset]->content), true);

                if (!is_array($nodes))
                {
                    throw new BadProcessorDocumentException($documents[$offset], 'list is only partially downloaded');
                }
                elseif (isset($nodes['errors']))
                {
                    $data = [];

                    break;
                }

			foreach ($nodes as $root)
			{
				$mediaMalId = Strings::makeInteger($media === Media::Anime ? $root['anime_id'] : $root['manga_id']);
				$score      = Strings::makeInteger($root['score']);

                $startDate = $root['start_date_string'] ?? '00-00-00';

                if (preg_match('#^\d{2}-\d{2}-\d{2}$#', $startDate))
                {
                    [$m, $d, $y] = explode('-', $startDate);

                    $startDate = sprintf('%4$s%3$s-%1$s-%2$s', $m, $d, $y, $y > $year ? '19' : '20');
                }
                else
                {
                    $startDate = '0000-00-00';
                }

                $finishDate = $root['finish_date_string'] ?? '00-00-00';

                if (preg_match('#^\d{2}-\d{2}-\d{2}$#', $finishDate))
                {
                    [$m, $d, $y] = explode('-', $finishDate);

                    $finishDate = sprintf('%4$s%3$s-%1$s-%2$s', $m, $d, $y, $y > $year ? '19' : '20');
                }
                else
                {
                    $finishDate = '0000-00-00';
                }

				$status     = Strings::makeEnum($root['status'], [
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
                        $finishedEpisodes = Strings::makeInteger($root['num_watched_episodes']);
                        
                        if ($root['is_rewatching'])
                        {
                            $finishedEpisodes = Strings::makeInteger($root['anime_num_episodes']);
                        }
                        
						break;
					case Media::Manga:
						$finishedChapters = Strings::makeInteger($root['num_read_chapters']);
						$finishedVolumes  = Strings::makeInteger($root['num_read_volumes']);

                        if ($root['is_rereading'])
                        {
                            $finishedChapters = Strings::makeInteger($root['manga_num_chapters']);
                            $finishedVolumes  = Strings::makeInteger($root['manga_num_volumes']);
                        }
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
            }

            $ydm = false;

            foreach ($data as $entry)
            {
                [, $m, $d] = explode('-', $entry['start_date']);

                if (intval($m) > 12)
                {
                    $ydm = true;

                    break;
                }

                if (intval($d) > 12)
                {
                    break;
                }

                [, $m, $d] = explode('-', $entry['end_date']);

                if (intval($m) > 12)
                {
                    $ydm = true;

                    break;
                }

                if (intval($d) > 12)
                {
                    break;
                }
            }

            if ($ydm)
            {
                foreach ($data as $key => $entry)
                {
                    $data[$key]['start_date'] = sprintf('%1$s-%3$s-%2$s', ...explode('-', $entry['start_date']));

                    $data[$key]['end_date'] = sprintf('%1$s-%3$s-%2$s', ...explode('-', $entry['end_date']));
                }
            }

			Database::insert('usermedia', $data);

			$user = &$context->user;

			R::store($user);
		}
	}
}
