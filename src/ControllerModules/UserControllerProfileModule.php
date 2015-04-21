<?php
class UserControllerProfileModule extends AbstractUserControllerModule
{
    public static function getText(ViewContext $viewContext, $media)
    {
        return $viewContext->user->name;
    }

    public static function getUrlParts()
    {
        return ['', 'profile'];
    }

    public static function getMediaAvailability()
    {
        return [];
    }

    public static function work(&$controllerContext, &$viewContext)
    {
        $viewContext->viewName = 'user-profile';
        $viewContext->meta->title = $viewContext->user->name . '\'s profile &#8212; ' . Config::$title;
        $viewContext->meta->description = $viewContext->user->name . '\'s profile.';
        WebMediaHelper::addEntries($viewContext);
        WebMediaHelper::addMiniSections($viewContext);
        WebMediaHelper::addCustom($viewContext);
        
        $viewContext->yearsOnMal = null;
        
        if (intval($viewContext->user->join_date)) {
            list ($year, $month, $day) = explode('-', $viewContext->user->join_date);
            $time = mktime(0, 0, 0, $month, $day, $year);
            $diff = time() - $time;
            $diff /= 3600 * 24;
            $viewContext->yearsOnMal = $diff / 361.25;
        }
        
        $viewContext->friends = $viewContext->user->getFriends();
        
        $viewContext->finished = [];
        $viewContext->meanUserScore = [];
        $viewContext->meanGlobalScore = [];
        $viewContext->franchiseCount = [];
        $viewContext->mismatchedCount = [];
        
        foreach (Media::getConstList() as $media) {
            $list = $viewContext->user->getMixedUserMedia($media);
            
            $listFinished = UserMediaFilter::doFilter($list, UserMediaFilter::finished());
            $viewContext->finished[$media] = count($listFinished);
            unset($listFinished);
            
            $listNonPlanned = UserMediaFilter::doFilter($list, UserMediaFilter::nonPlanned());
            $viewContext->meanUserScore[$media] = RatingDistribution::fromEntries($listNonPlanned)->getMeanScore();
            $viewContext->meanGlobalScore[$media] = Model_MixedUserMedia::getRatingDistribution($media)->getMeanScore();
            $franchises = Model_MixedUserMedia::getFranchises($listNonPlanned);
            $viewContext->franchiseCount[$media] = count(array_filter($franchises, function($franchise) { return count($franchise->ownEntries) > 1; }));
            unset($franchises);
            unset($listNonPlanned);

            if ($media == Media::Anime) {
                $viewContext->episodes = array_sum(array_map(function($mixedMediaEntry) { return $mixedMediaEntry->finished_episodes; }, $list));
            } else {
                $viewContext->chapters = array_sum(array_map(function($mixedMediaEntry) { return $mixedMediaEntry->finished_chapters; }, $list));
            }
            
            $mismatched = $viewContext->user->getMismatchedUserMedia($list);
            $viewContext->mismatchedCount[$media] = count($mismatched);
            unset($mismatched);
            unset($list);
        }
    }
}
