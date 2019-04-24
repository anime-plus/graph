<?php
class UserControllerFavoritesModule extends AbstractUserControllerModule
{
    public static function getText(ViewContext $viewContext, $media)
    {
        return 'Favorites';
    }

    public static function getUrlParts()
    {
        return ['favorites'];
    }

    public static function getMediaAvailability()
    {
        return [Media::Anime, Media::Manga];
    }

    public static function getOrder()
    {
        return 4;
    }

    public static function work(&$controllerContext, &$viewContext)
    {
        $viewContext->viewName = 'user-favorites';
        $viewContext->meta->title = $viewContext->user->name . ' - Favorites (' . Media::toString($viewContext->media) . ') - ' . Config::$title;
        $viewContext->meta->description = $viewContext->user->name . '\'s ' . Media::toString($viewContext->media) . ' favorites.';
        WebMediaHelper::addHighcharts($viewContext);
        WebMediaHelper::addTablesorter($viewContext);
        WebMediaHelper::addInfobox($viewContext);
        WebMediaHelper::addEntries($viewContext);
        WebMediaHelper::addCustom($viewContext);
        
        $list = $viewContext->user->getMixedUserMedia($viewContext->media);
        $listNonPlanned = UserMediaFilter::doFilter($list, UserMediaFilter::nonPlanned());
        
        $favCreators = MediaCreatorDistribution::fromEntries($listNonPlanned);
        $favGenres = MediaGenreDistribution::fromEntries($listNonPlanned);
        $favYears = MediaYearDistribution::fromEntries($listNonPlanned);
        $favDecades = MediaDecadeDistribution::fromEntries($listNonPlanned);
        $favTypes = MediaTypeDistribution::fromEntries($listNonPlanned);
        $viewContext->favCreators = $favCreators;
        $viewContext->favGenres = $favGenres;
        $viewContext->favYears = $favYears;
        $viewContext->favDecades = $favDecades;
        $viewContext->favTypes = $favTypes;
        
        $distMeanScore = [];
        $distTimeSpent = [];
        
        foreach ([$favCreators, $favGenres, $favDecades, $favYears] as $dist) {
            $meanScore = [];
            $timeSpent = [];
            
            foreach ($dist->getGroupsKeys(AbstractDistribution::IGNORE_NULL_KEY) as $safeKey => $key) {
                $meanScore[$safeKey] = 0;
                $timeSpent[$safeKey] = 0;
                $subEntries = $dist->getGroupEntries($key);
                $scoreCount = 0;
                
                foreach ($subEntries as $entry) {
                    $timeSpent[$safeKey] += $entry->finished_duration;
                    $meanScore[$safeKey] += $entry->score;
                    $scoreCount += $entry->score > 0;
                }
                
                $meanScore[$safeKey] /= max(1, $scoreCount);
            }
            
            $distMeanScore[get_class($dist)] = $meanScore;
            $distTimeSpent[get_class($dist)] = $timeSpent;
        }
        
        $viewContext->creatorScores = $distMeanScore[get_class($favCreators)];
        $viewContext->genreScores = $distMeanScore[get_class($favGenres)];
        $viewContext->yearScores = $distMeanScore[get_class($favYears)];
        $viewContext->decadeScores = $distMeanScore[get_class($favDecades)];
        $viewContext->creatorTimeSpent = $distTimeSpent[get_class($favCreators)];
        $viewContext->genreTimeSpent = $distTimeSpent[get_class($favGenres)];
        
        $viewContext->typePercentages = TextHelper::roundPercentages($favTypes->getGroupsSizes());
        
        $viewContext->genreValues = DistributionEvaluator::evaluate($favGenres);
        $viewContext->creatorValues = DistributionEvaluator::evaluate($favCreators);
    }
}
