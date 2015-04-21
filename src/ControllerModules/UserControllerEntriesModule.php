<?php
class UserControllerEntriesModule extends AbstractUserControllerModule
{
	public static function getUrlParts()
	{
		return ['entries'];
	}

	public static function getMediaAvailability()
	{
		return [];
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		$sender = $_GET['sender'];
		$filterParam = isset($_GET['filter-param']) ? $_GET['filter-param'] : null;
		if (isset($_GET['media']) and in_array($_GET['media'], Media::getConstList()))
		{
			$viewContext->media = $_GET['media'];
		}

		$viewContext->viewName = 'user-entries-' . $sender;
		$viewContext->layoutName = 'layout-ajax';
		$viewContext->filterParam = $filterParam;
		$list = $viewContext->user->getMixedUserMedia($viewContext->media);

		$computeMeanScore = null;
		switch ($sender)
		{
			case 'ratings':
				$filter = UserMediaFilter::combine(
					UserMediaFilter::nonPlanned(),
					UserMediaFilter::score($filterParam)
				);
				break;

			case 'length':
				$filter = UserMediaFilter::combine(
					UserMediaFilter::nonPlanned(),
					UserMediaFilter::nonMovie(),
					UserMediaFilter::lengthGroup($filterParam)
				);
				$computeMeanScore = true;
				break;

			case 'type':
				$filter = UserMediaFilter::combine(
					UserMediaFilter::nonPlanned(),
					UserMediaFilter::type($filterParam)
				);
				$computeMeanScore = true;
				break;

			case 'year':
				$filter = UserMediaFilter::combine(
					UserMediaFilter::nonPlanned(),
					UserMediaFilter::publishedYear($filterParam)
				);
				$computeMeanScore = true;
				break;

			case 'decade':
				$filter = UserMediaFilter::combine(
					UserMediaFilter::nonPlanned(),
					UserMediaFilter::publishedDecade($filterParam)
				);
				$computeMeanScore = true;
				break;

			case 'creator':
				$filter = UserMediaFilter::combine(
					UserMediaFilter::nonPlanned(),
					UserMediaFilter::creator($filterParam, $list)
				);
				switch ($viewContext->media)
				{
					case Media::Anime:
						$table = 'animeproducer';
						break;
					case Media::Manga:
						$table = 'mangaauthor';
						break;
					default:
						throw new BadMediaException();
				}
				$viewContext->genreName = R::getAll('SELECT * FROM ' . $table . ' WHERE mal_id = ?', [$filterParam])[0]['name'];
				$computeMeanScore = true;
				break;

			case 'genre':
				$filter = UserMediaFilter::combine(
					UserMediaFilter::nonPlanned(),
					UserMediaFilter::genre($filterParam, $list)
				);
				$viewContext->genreName = R::getAll('SELECT * FROM mediagenre WHERE mal_id = ?', [$filterParam])[0]['name'];
				$computeMeanScore = true;
				break;

			case 'franchises':
				$filter = UserMediaFilter::nonPlanned();
				break;
			case 'mismatches':

				$filter = null;
				break;

			default:
				throw new Exception('Unknown sender (' . $sender . ')');
		}

		$list = UserMediaFilter::doFilter($list, $filter);
		$isPrivate = $viewContext->user->isUserMediaPrivate($viewContext->media);

		if (!$isPrivate)
		{
			if ($computeMeanScore)
			{
				$dist = RatingDistribution::fromEntries($list);
				$viewContext->meanScore = $dist->getMeanScore();
			}
			if ($sender == 'franchises')
			{
				$franchises = Model_MixedUserMedia::getFranchises($list);
				foreach ($franchises as &$franchise)
				{
					$dist = RatingDistribution::fromEntries($franchise->ownEntries);
					$franchise->meanScore = $dist->getMeanScore();
				}
				unset($franchise);
				DataSorter::sort($franchises, DataSorter::MeanScore);
				$viewContext->franchises = array_filter($franchises, function($franchise) { return count($franchise->ownEntries) > 1; });
			}
			elseif ($sender == 'mismatches')
			{
				$entries = $viewContext->user->getMismatchedUserMedia($list);
				DataSorter::sort($entries, DataSorter::Title);
				$viewContext->entries = $entries;
			}
			else
			{
				DataSorter::sort($list, DataSorter::Title);
				$viewContext->entries = $list;
			}
		}

		$viewContext->isPrivate = $isPrivate;
	}
}
