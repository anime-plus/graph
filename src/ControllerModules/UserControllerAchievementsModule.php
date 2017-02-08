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
        $imgFiles = scandir(Config::$imageDirectory . DIRECTORY_SEPARATOR . 'achievement');
        $definitions = array_fill_keys(Media::getConstList(), []);
        
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') as $file) {
            $definition = TextHelper::loadJson($file);
            
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

        $evaluators = [
            'title' => function ($groupData) use ($listFinished) {
                $entries = UserMediaFilter::doFilter($listFinished, UserMediaFilter::givenMedia($groupData->requirement->titles));
                
                return [count($entries), $entries];
            },
            'genre-title' => function ($groupData) use ($listFinished) {
                $entries1 = UserMediaFilter::doFilter($listFinished, UserMediaFilter::genre($groupData->requirement->genre, $listFinished));
                
                $entries2 = !empty($groupData->requirement->titles) ? UserMediaFilter::doFilter($listFinished, UserMediaFilter::givenMedia($groupData->requirement->titles)) : [];
                
                $entries = array_merge($entries1, $entries2);
                
                $entries = array_intersect_key($entries, array_unique(array_map(function ($entry) {
                    return $entry->media . $entry->mal_id;
                }, $entries)));
                
                return [count($entries), $entries];
            },
            'finished' => function ($groupData) use ($listFinished) {
                return [count($listFinished), null];
            },
            'dropped-0' => function ($groupData) use ($listFinished, $listDropped) {
                if (count($listDropped) > 0) {
                    return [0, null];
                }
                
                return [count($listFinished), null];
            },
            'score' => function ($groupData) use ($listNonPlanned, $distribution) {
                if ($distribution->getRatedCount() > 0) {
                    return [$distribution->getMeanScore(), null];
                }
                
                return [null, null];
            },
            'release-old' => function ($groupData) use ($listFinished) {
                $entries = UserMediaFilter::doFilter($listFinished, function ($entry) {
                    $yearTo = substr($entry->published_to, 0, 4);
                    
                    return $yearTo !== '????' && intval($yearTo) <= 1980;
                });
                
                return [count($entries), $entries];
            },
            'release-classic' => function ($groupData) use ($listFinished) {
                $entries = UserMediaFilter::doFilter($listFinished, function ($entry) {
                    $yearFrom = substr($entry->published_from, 0, 4);
                    
                    $yearTo = substr($entry->published_to, 0, 4);
                    
                    return $yearFrom !== '????' && intval($yearFrom) >= 1981 && $yearTo !== '????' && intval($yearTo) <= 2000;
                });
                
                return [count($entries), $entries];
            },
            'duration-short' => function ($groupData) use ($listFinished) {
                $entries = UserMediaFilter::doFilter($listFinished, function ($entry) {
                    $duration = $entry->duration;
                    
                    return $duration <= 15;
                });
                
                return [count($entries), $entries];
            },
            'episode-long' => function ($groupData) use ($listFinished) {
                $entries = UserMediaFilter::doFilter($listFinished, function ($entry) {
                    $episode = $entry->episodes;
                    
                    return $episode >= 100;
                });
                
                return [count($entries), $entries];
            },
            'volume-long' => function ($groupData) use ($listFinished) {
                $entries = UserMediaFilter::doFilter($listFinished, function ($entry) {
                    $volume = $entry->volumes;
                    
                    return $volume >= 15;
                });
                
                return [count($entries), $entries];
            },
            'pervert' => function ($groupData) use ($viewContext, $listFinished) {
                $entriesCount = count($listFinished);
                
                if ($entriesCount > 0) {
                    $entriesEcchi = UserMediaFilter::doFilter($listFinished, UserMediaFilter::genre(9, $listFinished));
                    
                    $entriesHentai = UserMediaFilter::doFilter($listFinished, UserMediaFilter::genre(12, $listFinished));
                    
                    $entries = array_merge($entriesEcchi, $entriesHentai);
                    
                    $entries = array_intersect_key($entries, array_unique(array_map(function ($entry) {
                        return $entry->media . $entry->mal_id;
                    }, $entries)));
                    
                    $score = 100 / $entriesCount * (count($entriesEcchi) * 2 + count($entriesHentai) * 4);
                    
                    if ($score > 100) {
                        $score = 100;
                    }
                    
                    return [$score, $entries];
                }
                
                return [0, null];
            },
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
