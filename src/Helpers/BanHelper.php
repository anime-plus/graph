<?php
class BanHelper extends Singleton
{
	const USER_BAN_NONE = 0;
	const USER_BAN_QUEUE_ONLY = 1;
	const USER_BAN_TOTAL = 2;

	private static $bannedUsers;
	private static $bannedGenres;
	private static $bannedCreators;
	private static $bannedGenresForRecs;
	private static $bannedFranchiseCoupling;

	public static function doInit()
	{
		$list = TextHelper::loadSimpleList(Config::$bannedUsersListPath);
		foreach ($list as $line)
		{
			$tmp = strpos($line, "\t") === false
				? [$line, self::USER_BAN_TOTAL]
				: explode("\t", $line);
			list ($userName, $banType) = $tmp;
			self::$bannedUsers[strtolower($userName)] = $banType;
		}

		self::$bannedGenres = TextHelper::loadSimpleList(Config::$bannedGenresListPath);
		self::$bannedCreators = TextHelper::loadSimpleList(Config::$bannedCreatorsListPath);
		self::$bannedGenresForRecs = TextHelper::loadSimpleList(Config::$bannedGenresForRecsListPath);
		self::$bannedFranchiseCoupling = TextHelper::loadJson(Config::$bannedFranchiseCouplingListPath, true);
	}
    
	public static function getUserBanState($userName)
	{
		return isset(self::$bannedUsers[strtolower($userName)])
			? self::$bannedUsers[strtolower($userName)]
			: self::USER_BAN_NONE;
	}

	public static function setUserBanState($userName, $banState)
	{
		if ($banState != self::USER_BAN_NONE)
		{
			self::$bannedUsers[strtolower($userName)] = $banState;
		}
		else
		{
			unset(self::$bannedUsers[strtolower($userName)]);
		}

		$list = [];
		foreach (self::$bannedUsers as $user => $banState)
		{
			$list []= $user . "\t" . $banState;
		}
		TextHelper::putSimpleList(Config::$bannedUsersListPath, $list);
	}

	public static function isGenreBanned($media, $genreId)
	{
		return in_array($media . $genreId, self::$bannedGenres);
	}

	public static function isCreatorBanned($media, $creatorId)
	{
		return in_array($media . $creatorId, self::$bannedCreators);
	}

	public static function isGenreBannedForRecs($media, $genreId)
	{
		return in_array($media . $genreId, self::$bannedGenresForRecs);
	}
    
	public static function isFranchiseCouplingBanned($media1, $idMal1, $media2, $idMal2)
	{
        $key1 = $media1 . $idMal1;
        
		$key2 = $media2 . $idMal2;
        
        if (isset(self::$bannedFranchiseCoupling[$key2]) && in_array($key1, self::$bannedFranchiseCoupling[$key2])) {
            return true;
        }
        
		return false;
	}
}

BanHelper::init();
