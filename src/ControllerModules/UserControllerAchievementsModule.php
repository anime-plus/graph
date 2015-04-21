<?php
class UserControllerAchievementsModule extends AbstractUserControllerModule
{
    public static function getText(ViewContext $viewContext, $media)
    {
        return 'Achievements';
    }
    
    public static function getUrlParts()
    {
        return ['achievements'];
    }
    
    public static function getMediaAvailability()
    {
        return [Media::Anime, Media::Manga];
    }
    
    public static function getOrder()
    {
        return 6;
    }
    
    private static function getThreshold($achievement)
    {
        $threshold = $achievement->threshold;
        
        if (preg_match('/^([0-9.]+)\+$/', $threshold, $matches)) {
            return [floatval($matches[1]), null];
        } elseif (preg_match('/^([0-9.]+)(\.\.|-)([0-9.]+)$/', $threshold, $matches)) {
            return [floatval($matches[1]), floatval($matches[3])];
        }
        
        throw new Exception('Invalid threshold: ' . $threshold);
    }
    
    public static function getAchievementsDefinitions()
    {
        $dir = Config::$achievementsDefinitionsDirectory;
        $imgFiles = scandir(Config::$mediaDirectory . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'ach');
        $definitions = array_fill_keys(Media::getConstList(), []);
        
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') as $file) {
            $definition = TextHelper::loadJson($file);
            
            if ($definition->{'wiki-title'} === null) {
                $definition->{'wiki-title'} = 'Unknown group';
            }
            
            $prevAch = null;
            
            foreach ($definition->achievements as &$ach) {
                foreach ($imgFiles as $f) {
                    if (preg_match('/' . $ach->id . '[^0-9a-zA-Z_-]/', $f)) {
                        $ach->path = $f;
                    }
                }
                
                $ach->next = null;
            }

            foreach ($definition->achievements as &$ach) {
                if ($prevAch !== null) {
                    $prevAch->next = $ach;
                }
                
                $ach->prev = $prevAch;
                $prevAch = &$ach;
            }
            
            unset($ach);
            unset($prevAch);
            
            $definitions[$definition->media] []= $definition;
        }
        
        foreach (Media::getConstList() as $key) {
            uasort($definitions[$key], function($a, $b)
            {
                return $a->order - $b->order;
            });
        }
        
        return $definitions;
    }
    
    public static function work(&$controllerContext, &$viewContext)
    {
        $viewContext->viewName = 'user-achievements';
        $viewContext->meta->title = $viewContext->user->name . ' &#8212; Achievements (' . Media::toString($viewContext->media) . ') &#8212; ' . Config::$title;
        $viewContext->meta->description = $viewContext->user->name . '\'s ' . Media::toString($viewContext->media) . ' achievements.';
        WebMediaHelper::addEntries($viewContext);
        WebMediaHelper::addCustom($viewContext);

        $achList = self::getAchievementsDefinitions();

        $list = $viewContext->user->getMixedUserMedia($viewContext->media);
        $listFinished = UserMediaFilter::doFilter($list, UserMediaFilter::finished());
        $listNonPlanned = UserMediaFilter::doFilter($list, UserMediaFilter::nonPlanned());
        $listDropped = UserMediaFilter::doFilter($list, UserMediaFilter::dropped());
        $distribution = RatingDistribution::fromEntries($listNonPlanned);

        $evaluators =
        [
            'given-titles' => function($groupData) use ($listFinished)
            {
                $entriesOwned = UserMediaFilter::doFilter($listFinished, UserMediaFilter::givenMedia($groupData->requirement->titles));
                
                return [count($entriesOwned), $entriesOwned];
            },
            'genre-titles' => function($groupData) use ($viewContext, $listFinished)
            {
                $entriesOwned1 = UserMediaFilter::doFilter($listFinished, UserMediaFilter::genre($groupData->requirement->genre, $listFinished));
                $entriesOwned2 = !empty($groupData->requirement->titles) ? UserMediaFilter::doFilter($listFinished, UserMediaFilter::givenMedia($groupData->requirement->titles)) : [];
                $entriesOwned = array_merge($entriesOwned1, $entriesOwned2);
                #array unique w/ callback
                $entriesOwned = array_intersect_key($entriesOwned, array_unique(array_map(function($e) { return $e->media . $e->mal_id; }, $entriesOwned)));
                
                return [count($entriesOwned), $entriesOwned];
            },
            'finished-titles' => function($groupData) use ($listFinished)
            {
                return [count($listFinished), null];
            },
            'mean-score' => function($groupData) use ($listNonPlanned, $distribution)
            {
                if ($distribution->getRatedCount() > 0) {
                    return [$distribution->getMeanScore(), null];
                }
                
                return [null, null];
            },
            'no-drop' => function($groupData) use ($listFinished, $listDropped)
            {
                if (count($listDropped) > 0) {
                    return [0, null];
                }
                
                return [count($listFinished), null];
            },
            'old-titles' => function($groupData) use ($listFinished)
            {
                $entriesOwned = UserMediaFilter::doFilter($listFinished, function($row)
                {
                    $year = substr($row->published_to, 0, 4);
                    return $year != '????' and intval($year) <= 1980;
                });
                
                return [count($entriesOwned), $entriesOwned];
            }
        ];
        
        $achievements = [];
        $hiddenCount = 0;
        
        foreach ($achList[$viewContext->media] as $group => $groupData) {
            //get subject and entries basing on requirement type
            $evaluator = $evaluators[$groupData->requirement->type];
            list ($subject, $entriesOwned) = $evaluator($groupData);
            
            $groupData->achievements = array_reverse($groupData->achievements);
            
            if ($subject === null) {
                continue;
            }
            
            //give first achievement for which the subject fits into its threshold
            $localAchievements = [];
            
            foreach ($groupData->achievements as &$ach) {
                list($a, $b) = self::getThreshold($ach);
                $ach->thresholdLeft = $a;
                $ach->thresholdRight = $b;
                $ach->earned = ((($subject >= $a) or ($a === null)) and (($subject <= $b) or ($b === null)));
                
                if ($ach->next and $ach->next->earned) {
                    $ach->earned = true;
                    $ach->hidden = true;
                    $hiddenCount ++;
                } else {
                    $ach->hidden = false;
                }
                
                if ($ach->earned) {
                    //put additional info
                    if (!empty($entriesOwned)) {
                        DataSorter::sort($entriesOwned, DataSorter::Title);
                        $ach->entries = $entriesOwned;
                    }
                    
                    $ach->progress = 100;
                    $ach->subject = round($subject, 2);
                    
                    if ($ach->next) {
                        $ach->progress = ($subject - $a) * 100.0 / ($ach->next->thresholdLeft - $a);
                    }
                    
                    $localAchievements []= $ach;
                }
            }
            
            $achievements = array_merge($achievements, array_reverse($localAchievements));
        }
        
        $viewContext->achievements = $achievements;
        $viewContext->private = $viewContext->user->isUserMediaPrivate($viewContext->media);
        $viewContext->hiddenCount = $hiddenCount;
    }
}
