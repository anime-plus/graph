<?php
class AdminControllerProcessorModule extends AbstractControllerModule
{
	public static function getUrlParts()
	{
		return ['a/process'];
	}

	public static function url()
	{
		return '/a/process';
	}

	private static function getChosenMedia($modelIds)
	{
		$chosenMedia =
		[
			Media::Anime => [],
			Media::Manga => [],
		];

		foreach ($modelIds as $modelId)
		{
			if (!preg_match('/([AM])(\d+)(-\1(\d+))?/i', $modelId, $matches))
			{
				throw new Exception('Bad media ID: ' . $modelId);
			}

			$media = strtoupper($matches[1]);
			$mediaId1 = intval($matches[2]);
			$mediaId2 = isset($matches[4]) ? intval($matches[4]) : $mediaId1;
			foreach (range($mediaId1, $mediaId2) as $mediaId)
			{
				$chosenMedia[$media] []= $mediaId;
			}
		}

		foreach ($chosenMedia as $media => $ids)
		{
			$chosenMedia[$media] = array_unique($ids);
		}

		return $chosenMedia;
	}

	private static function getChosenUsers($modelIds)
	{
		$chosenUsers = [];
		foreach ($modelIds as $modelId)
		{
			$chosenUsers []= $modelId;
		}
		return $chosenUsers;
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		try
		{
			if (empty($_POST['sender']))
			{
				throw new Exception('No sender specified');
			}
			$sender = $_POST['sender'];

			if (empty($_POST['action']))
			{
				throw new Exception('No action specified');
			}
			$action = $_POST['action'];

			if (empty($_POST['model-ids']))
			{
				throw new Exception('No model ids specified');
			}
			$modelIds = array_map('trim', preg_split('/[,;]/', $_POST['model-ids']));

			$chosenMedia = [];
			$chosenUsers = [];
			switch ($sender)
			{
				case 'media':
					$chosenMedia = self::getChosenMedia($modelIds);
					break;
				case 'user':
					$chosenUsers = self::getChosenUsers($modelIds);
					break;
				default:
					throw new Exception('Unknown sender: ' . $sender);
			}

			if ($action == 'refresh')
			{
				$num = 0;
				$startTime = microtime(true);
				$mediaProcessors =
				[
					Media::Anime => new AnimeProcessor(),
					Media::Manga => new MangaProcessor(),
				];
				$userProcessor = new UserProcessor();

				foreach ($chosenMedia as $media => $ids)
				{
					foreach ($ids as $id)
					{
						$mediaProcessors[$media]->process($id);
						++ $num;
					}
				}
				foreach ($chosenUsers as $user)
				{
					$userProcessor->process($user);
					++ $num;
				}

				$viewContext->messageType = 'info';
				$viewContext->message = sprintf('Successfully processed %d entities in %.02fs', $num, microtime(true) - $startTime);
			}

			elseif ($action == 'wipe-cache')
			{
				$deleted = 0;
				foreach ($chosenUsers as $userName)
				{
					$cache = new Cache();
					$cache->setPrefix($userName);

					foreach ($cache->getAllFiles() as $path)
					{
						$deleted ++;
						unlink($path);
					}
				}

				$viewContext->messageType = 'info';
				$viewContext->message = 'Deleted ' . $deleted . ' files';
			}

			elseif ($action == 'unban' or $action == 'soft-ban' or $action == 'hard-ban')
			{
				switch ($action)
				{
					case 'unban':
						$banState = BanHelper::USER_BAN_NONE;
						break;
					case 'soft-ban':
						$banState = BanHelper::USER_BAN_QUEUE_ONLY;
						break;
					case 'hard-ban':
						$banState = BanHelper::USER_BAN_TOTAL;
						break;
					default:
						throw new Exception('Wrong ban state');
				}
				$changed = 0;
				foreach ($chosenUsers as $userName)
				{
					BanHelper::setUserBanState($userName, $banState);
					++ $changed;
				}

				$viewContext->messageType = 'info';
				$viewContext->message = sprintf('Successfully updated %d users', $changed);
			}

			elseif ($action == 'reset-franchise')
			{
				$num = 0;

				foreach ($chosenMedia as $media => $ids)
				{
					$query = 'UPDATE media SET franchise = NULL WHERE media = ? AND mal_id IN (' . R::genSlots($ids) . ')';
					R::exec($query, array_merge([$media], $ids));
					$num += count($ids);
				}

				$viewContext->messageType = 'info';
				$viewContext->message = sprintf('Successfully reset franchise for %d entities. Don\'t forget to refresh them now!', $num);
			}

			elseif ($action == 'remove')
			{
				$num = 0;

				foreach ($chosenMedia as $media => $ids)
				{
					$query = 'DELETE FROM media WHERE media = ? AND mal_id IN (' . R::genSlots($ids) . ')';
					R::exec($query, array_merge([$media], $ids));
					$num += count($ids);
				}

				$viewContext->messageType = 'info';
				$viewContext->message = sprintf('Successfully removed %d entities.', $num);
			}

			else
			{
				throw new Exception('Unknown action: ' . $action);
			}
		}
		catch (Exception $e)
		{
			$viewContext->messageType = 'error';
			$viewContext->message = $e->getMessage();
		}

		$viewContext->viewName = 'admin-index';
		$viewContext->meta->title = 'Admin &#8212; ' . Config::$title;
		WebMediaHelper::addCustom($viewContext);
	}
}
