<?php
class UserControllerRatingsModule extends AbstractUserControllerModule
{
    public static function getText(ViewContext $viewContext, $media)
    {
        return 'Ratings';
    }

    public static function getUrlParts()
    {
        return ['ratings'];
    }

    public static function getMediaAvailability()
    {
        return [Media::Anime, Media::Manga];
    }

    public static function getOrder()
    {
        return 2;
    }

    public static function work(&$controllerContext, &$viewContext)
    {
        $viewContext->viewName = 'user-ratings';
        $viewContext->meta->title = $viewContext->user->name . ' - Ratings (' . Media::toString($viewContext->media) . ') - ' . Config::$title;
        $viewContext->meta->description = $viewContext->user->name . '\'s ' . Media::toString($viewContext->media) . ' ratings.';
        WebMediaHelper::addHighcharts($viewContext);
        WebMediaHelper::addFarbtastic($viewContext);
        WebMediaHelper::addInfobox($viewContext);
        WebMediaHelper::addEntries($viewContext);
        WebMediaHelper::addCustom($viewContext);

        $list = $viewContext->user->getMixedUserMedia($viewContext->media);
        $list = UserMediaFilter::doFilter($list, UserMediaFilter::nonPlanned());
        $viewContext->ratingDistribution = RatingDistribution::fromEntries($list);
        $viewContext->ratingTimeDistribution = RatingTimeDistribution::fromEntries($list);
        $listNoMovies = UserMediaFilter::doFilter($list, UserMediaFilter::nonMovie());
        $viewContext->lengthDistribution = MediaLengthDistribution::fromEntries($listNoMovies);

        $f = explode('-', $viewContext->user->join_date);
        
        if (count($f) != 3) {
            $viewContext->earliestTimeKnown = null;
            $viewContext->meanTime = null;
        } else {
            list($year, $month, $day) = $f;
            $earliest = mktime(0, 0, 0, $month, $day, $year);
            $totalTime = 0;
            
            foreach ($list as $mixedUserMedia) {
                $totalTime += $mixedUserMedia->finished_duration;
                
                foreach ([$mixedUserMedia->start_date, $mixedUserMedia->end_date] as $k) {
                    $f = explode('-', $k);
                    
                    if (count($f) != 3) {
                        continue;
                    }
                    
                    $year = intval($f[0]);
                    $month = intval($f[1]);
                    $day = intval($f[2]);
                    
                    if (!$year or !$month or !$day) {
                        continue;
                    }
                    
                    $time = mktime(0, 0, 0, $month, $day, $year);
                    
                    if ($time < $earliest) {
                        $earliest = $time;
                    }
                }
            }
            
            $viewContext->earliestTimeKnown = $earliest;
            $viewContext->meanTime = $totalTime / max(1, (time() - $earliest) / (24. * 3600.0));
        }
    }
}
