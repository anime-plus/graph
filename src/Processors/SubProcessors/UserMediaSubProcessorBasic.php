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
        
        foreach (Media::getConstList() as $media) {
			$data = [];
            
            $range = $media === Media::Anime ? array_keys($this->animeArray) : array_keys($this->mangaArray);
            
            foreach ($range as $offset)
            {
                $nodes = json_decode(str_replace('<?xml encoding="utf-8"?>', '', $documents[$offset]->content), true);
                
                if ($nodes === null)
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
				$startDate  = preg_match('#^[0-9]{2}-[0-9]{2}-[0-9]{2}$#', $root['start_date_string']) ? sprintf('%3$s-%1$s-%2$s', ...explode('-', $root['start_date_string'])) : Strings::makeDate('');
                $startDate  = substr($startDate, 0, 2) > date('y', strtotime('+1 day')) ? '19' . $startDate : '20' . $startDate;
				$finishDate = preg_match('#^[0-9]{2}-[0-9]{2}-[0-9]{2}$#', $root['finish_date_string']) ? sprintf('%3$s-%1$s-%2$s', ...explode('-', $root['finish_date_string'])) : Strings::makeDate('');
                $finishDate = substr($finishDate, 0, 2) > date('y', strtotime('+1 day')) ? '19' . $finishDate : '20' . $finishDate;
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
						break;
					case Media::Manga:
						$finishedChapters = Strings::makeInteger($root['num_read_chapters']);
						$finishedVolumes  = Strings::makeInteger($root['num_read_volumes']);
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
            
            $us = 0;
            
            $eu = 0;
            
            foreach ($data as $entry)
            {
                if (preg_match('#^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$#', $entry['start_date']))
                {
                    $us++;
                }
                
                if (preg_match('#^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$#', $entry['end_date']))
                {
                    $us++;
                }
                
                if (preg_match('#^[0-9]{4}-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])$#', $entry['start_date']))
                {
                    $eu++;
                }
                
                if (preg_match('#^[0-9]{4}-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])$#', $entry['end_date']))
                {
                    $eu++;
                }
            }
            
            if ($eu > $us)
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
