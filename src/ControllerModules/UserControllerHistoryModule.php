<?php
class UserControllerHistoryModule extends AbstractUserControllerModule
{
    public static function getText(ViewContext $viewContext, $media)
    {
        return 'History';
    }

    public static function getUrlParts()
    {
        return ['history'];
    }

    public static function getMediaAvailability()
    {
        return [Media::Anime, Media::Manga];
    }

    public static function getOrder()
    {
        return 3;
    }

    public static function work(&$controllerContext, &$viewContext)
    {
        $viewContext->viewName = 'user-history';
        $viewContext->meta->title = $viewContext->user->name . ' - History (' . Media::toString($viewContext->media) . ') - ' . Config::$title;
        $viewContext->meta->description = $viewContext->user->name . '\'s ' . Media::toString($viewContext->media) . ' history.';
        WebMediaHelper::addHighcharts($viewContext);
        WebMediaHelper::addInfobox($viewContext);
        WebMediaHelper::addEntries($viewContext);
        WebMediaHelper::addCustom($viewContext);

        $list = $viewContext->user->getMixedUserMedia($viewContext->media);
        $listFinished = UserMediaFilter::doFilter($list, UserMediaFilter::finished());

        $monthlyHistoryGroups = [];
        $monthlyHistoryGroupsScores = [];
        $yearlyHistoryGroupsScores = [];
        $unknownEntries = [];
        $max = 0;
        foreach ($listFinished as $entry)
        {
            $key = $entry->end_date;
            list ($year, $month, $day) = array_map('intval', explode('-', $key));
            if (!$year || !$month)
            {
                $unknownEntries []= $entry;
                continue;
            }

            if (!isset($monthlyHistoryGroups[$year]))
            {
                $monthlyHistoryGroups[$year] = [];
            }
            if (!isset($monthlyHistoryGroups[$year][$month]))
            {
                $monthlyHistoryGroups[$year][$month] = [];
            }
            $monthlyHistoryGroups[$year][$month][] = $entry;
            $max = max($max, count($monthlyHistoryGroups[$year][$month]));
        }

        foreach ($monthlyHistoryGroups as $y => $months)
        {
            $countY = 0;

            $scoreY = 0;

            foreach ($months as $m => $entries)
            {
                $countM = 0;

                $scoreM = 0;

                foreach ($entries as $entry)
                {
                    if ($entry->score > 0)
                    {
                        $countY += 1;

                        $scoreY += $entry->score;

                        $countM += 1;

                        $scoreM += $entry->score;
                    }
                }

                $monthlyHistoryGroupsScores[$y][$m] = $countM > 0 ? round($scoreM / $countM, 2) : 0;
            }

            $yearlyHistoryGroupsScores[$y] = $countY > 0 ? round($scoreY / $countY, 2) : 0;
        }

        $count = 0;

        $score = 0;

        foreach ($unknownEntries as $entry)
        {
            if ($entry->score > 0)
            {
                $count += 1;

                $score += $entry->score;
            }
        }

        $unknownEntriesScore = $count > 0 ? round($score / $count, 2) : 0;

        krsort($monthlyHistoryGroups, SORT_NUMERIC);
        foreach ($monthlyHistoryGroups as &$group)
        {
            ksort($group, SORT_NUMERIC);
        }
        unset($group);
        $viewContext->monthlyHistoryMax = $max;
        $viewContext->monthlyHistoryGroups = $monthlyHistoryGroups;
        $viewContext->monthlyHistoryGroupsScores = $monthlyHistoryGroupsScores;
        $viewContext->yearlyHistoryGroupsScores = $yearlyHistoryGroupsScores;
        $viewContext->monthlyHistoryUnknownEntries = $unknownEntries;
        $viewContext->monthlyHistoryUnknownEntriesScore = $unknownEntriesScore;

        $dailyHistory = $viewContext->user->getHistory($viewContext->media);
        $dailyHistoryGroups = [];
        foreach ($dailyHistory as $historyEntry)
        {
            $key = date('Y-m-d', strtotime($historyEntry->timestamp));
            if (!isset($dailyHistoryGroups[$key]))
            {
                $dailyHistoryGroups[$key] = [];
            }
            $dailyHistoryGroups[$key] []= $historyEntry;
        }
        krsort($dailyHistoryGroups);
        $viewContext->dailyHistoryGroups = $dailyHistoryGroups;

        $viewContext->isPrivate = $viewContext->user->isUserMediaPrivate($viewContext->media);
    }
}
