<?php
class Config extends Singleton
{
    static $cronInterval;
    static $usersPerCronRun;
    static $usersPerCronRunMore;
    static $userQueuePath;
    static $userQueueMinWait;
    static $userQueueMaxAttempts;
    static $mediaPerCronRun;
    static $mediaPerCronRunMore;
    static $mediaQueuePath;
    static $mediaQueueMinWait;
    static $mediaQueueMaxAttempts;
    static $downloaderUserAgent;
    static $downloaderProxy;
    static $downloaderCookieFilePath;
    static $downloaderMaxParallelJobs;
    static $downloaderMaxTimeout;
    static $downloaderUseMultiHandles;
    static $mirrorPath;
    static $mirrorPurgeFailures;
    static $mirrorEnabled;
    static $cachePath;
    static $cacheEnabled;
    static $cacheTimeToLive;
    static $dbPath;
    static $dbCount;
    static $maxDbBindings;
    static $maxProcessingAttempts;
    static $transactionCommitFrequency;
    static $bannedUsersListPath;
    static $bannedGenresListPath;
    static $bannedCreatorsListPath;
    static $bannedGenresForRecsListPath;
    static $bannedFranchiseCouplingListPath;
    static $staticRecommendationListPath;
    static $achievementsDefinitionsDirectory;
    static $maxLogSize;
    static $logsPath;
    static $keepOldLogs;
    static $globalsCachePath;
    static $userQueueSizesPath;
    static $mediaDirectory;
    static $imageDirectory;
    static $mediaUrl;
    static $imageUrl;
    static $baseUrl;
    static $googleAnalyticsEnabled;
    static $adminPassword;
    static $maintenanceMessage;
    static $noticeMessage;
    static $sendReferrer;
    static $enforcedDomain;
    static $mail;
    static $title;
    
    public static function doInit()
    {
        $dataRootDir = join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'data', '']);
        $htmlRootDir = join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'public']);
        
        self::$title = 'graph.anime.plus';
        self::$mail = 'hello@anime.plus';
        
        self::$cronInterval = 5;
        self::$usersPerCronRun = 10;
        self::$usersPerCronRunMore = 20;
        self::$userQueuePath = $dataRootDir . 'queue-users.lst';
        self::$userQueueMinWait = 60 * 60;
        self::$userQueueMaxAttempts = 2;
        self::$mediaPerCronRun = 40;
        self::$mediaPerCronRunMore = 5;
        self::$mediaQueuePath = $dataRootDir . 'queue-media.lst';
        self::$mediaQueueMinWait = 7 * 24 * 60 * 60;
        self::$mediaQueueMaxAttempts = 2;
        
        self::$downloaderUserAgent = '';
        self::$downloaderProxy = null;
        self::$downloaderCookieFilePath = $dataRootDir . 'cookies.dat';
        self::$downloaderMaxParallelJobs = 2;
        self::$downloaderMaxTimeout = 10000;
        self::$downloaderUseMultiHandles = false;
        
        self::$mirrorEnabled = false;
        self::$mirrorPath = $dataRootDir . 'mirror';
        self::$mirrorPurgeFailures = true;
        self::$cacheEnabled = true;
        self::$cachePath = $dataRootDir . 'cache';
        self::$cacheTimeToLive = 60 * 60;
        
        self::$dbPath = $dataRootDir . 'db';
        self::$dbCount = 64;
        self::$transactionCommitFrequency = 20;
        self::$maxDbBindings = 50;
        self::$maxProcessingAttempts = 1;
        
        self::$bannedUsersListPath = $dataRootDir . 'banned-users.lst';
        self::$bannedGenresListPath = $dataRootDir . 'banned-genres.lst';
        self::$bannedCreatorsListPath = $dataRootDir . 'banned-creators.lst';
        self::$bannedGenresForRecsListPath = $dataRootDir . 'recs-banned-genres.lst';
        self::$bannedFranchiseCouplingListPath = $dataRootDir . 'banned-franchise-coupling.json';
        self::$staticRecommendationListPath = $dataRootDir . 'static-recommendations.lst';
        self::$achievementsDefinitionsDirectory = $dataRootDir . 'achievement';
        
        self::$maxLogSize = 1024 * 1024;
        self::$keepOldLogs = false;
        self::$logsPath = $dataRootDir . 'logs';
        self::$globalsCachePath = $dataRootDir . 'globals-cache.json';
        self::$userQueueSizesPath = $dataRootDir . 'queue-sizes.json';
        
        self::$mediaDirectory = $htmlRootDir . DIRECTORY_SEPARATOR . 'media';
        self::$imageDirectory = $htmlRootDir . DIRECTORY_SEPARATOR . 'image';
        self::$mediaUrl = '/media/';
        self::$imageUrl = '/image/';
        self::$baseUrl = isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/' : 'http://graph.anime.plus/';
        self::$googleAnalyticsEnabled = true;
        self::$adminPassword = '';
        self::$maintenanceMessage = null;
        self::$noticeMessage = '<a href="https://myanimelist.net/clubs.php?cid=67199" target="_blank">JOIN OUR CLUB SENPAI!</a>';
        self::$sendReferrer = true;
        self::$enforcedDomain = null;
    }
}

Config::init();
