<?php
class UserSubProcessorProfile extends UserSubProcessor
{
	const URL_PROFILE = 0;

	public function getURLs($userName)
	{
		return
		[
			self::URL_PROFILE => 'http://myanimelist.net/profile/' . $userName,
		];
	}

	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_PROFILE];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		if ($xpath->query('//title[text() = \'Invalid User\']')->length >= 1)
			throw new BadProcessorKeyException($context->key);

		$userName = Strings::removeSpaces(self::getNodeValue($xpath, '//title'));
		$userName = substr($userName, 0, strpos($userName, '\'s Profile'));
		$userName = str_replace('Top - ', '', $userName);
		$userName = Strings::removeSpaces($userName);
		if (empty($userName))
			throw new BadProcessorDocumentException($doc, 'User name missing');

		$pictureUrl     = self::getNodeValue($xpath, '//td[@class = \'profile_leftcell\']//img', null, 'src');
		$joinDate       = Strings::makeDate(self::getNodeValue($xpath, '//td[text() = \'Join Date\']/following-sibling::td'));
		$malId          = Strings::makeInteger(self::getNodeValue($xpath, '//input[@name = \'profileMemId\']', null, 'value'));
		$animeViewCount = Strings::makeInteger(self::getNodeValue($xpath, '//td[text() = \'Anime List Views\']/following-sibling::td'));
		$mangaViewCount = Strings::makeInteger(self::getNodeValue($xpath, '//td[text() = \'Manga List Views\']/following-sibling::td'));
		$commentCount   = Strings::makeInteger(self::getNodeValue($xpath, '//td[text() = \'Comments\']/following-sibling::td'));
		$postCount      = Strings::makeInteger(self::getNodeValue($xpath, '//td[text() = \'Forum Posts\']/following-sibling::td'));
		$birthday       = Strings::makeDate(self::getNodeValue($xpath, '//td[text() = \'Birthday\']/following-sibling::td'));
		$location       = Strings::removespaces(self::getNodeValue($xpath, '//td[text() = \'Location\']/following-sibling::td'));
		$website        = Strings::removeSpaces(self::getNodeValue($xpath, '//td[text() = \'Website\']/following-sibling::td'));
		$gender         = Strings::makeEnum(self::getNodeValue($xpath, '//td[text() = \'Gender\']/following-sibling::td'), ['Female' => UserGender::Female, 'Male' => UserGender::Male], UserGender::Unknown);

		$user = &$context->user;
		$user->name = $userName;
		$user->picture_url = $pictureUrl;
		$user->join_date = $joinDate;
		$user->mal_id = $malId;
		$user->comments = $commentCount;
		$user->posts = $postCount;
		$user->birthday = $birthday;
		$user->location = $location;
		$user->website = $website;
		$user->gender = $gender;
		$user->anime_views = $animeViewCount;
		$user->manga_views = $mangaViewCount;
		$user->processed = date('Y-m-d H:i:s');
		R::store($user);
	}
}
