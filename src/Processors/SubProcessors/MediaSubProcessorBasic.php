<?php
class MediaSubProcessorBasic extends MediaSubProcessor
{
	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_MEDIA];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		if ($xpath->query('//div[@class = \'badresult\']')->length >= 1)
			throw new BadProcessorKeyException($context->key);

		$title = Strings::removeSpaces(self::getNodeValue($xpath, '//h1/*/following-sibling::node()[1][self::text()]'));
		if (empty($title))
			throw new BadProcessorDocumentException($doc, 'empty title');

		//sub type
		$malSubType = strtolower(Strings::removeSpaces(self::getNodeValue($xpath, '//span[starts-with(text(), \'Type\')]/following-sibling::node()[self::text()]')));
		$subType = Strings::makeEnum($malSubType, [
			'tv'        => AnimeMediaType::TV,
			'ova'       => AnimeMediaType::OVA,
			'movie'     => AnimeMediaType::Movie,
			'special'   => AnimeMediaType::Special,
			'ona'       => AnimeMediaType::ONA,
			'music'     => AnimeMediaType::Music,
			'manga'     => MangaMediaType::Manga,
			'novel'     => MangaMediaType::Novel,
			'one-shot'  => MangaMediaType::Oneshot,
			'doujinshi' => MangaMediaType::Doujinshi,
			'manhwa'    => MangaMediaType::Manhwa,
			'manhua'    => MangaMediaType::Manhua,
			'oel'       => MangaMediaType::OEL,
			''          => $this->media == Media::Manga ? MangaMediaType::Unknown : AnimeMediaType::Unknown,
		], null);
		if ($subType === null)
			throw new BadProcessorDocumentException($doc, 'empty sub type');

		//picture
		$pictureUrl = self::getNodeValue($xpath, '//td[@class = \'borderClass\']//img', null, 'src');

		//rank
		$averageScore = Strings::makeFloat(self::getNodeValue($xpath, '//span[starts-with(text(), \'Score\')]/following-sibling::node()[self::text()]'));
		$averageScoreUsers = Strings::extractInteger(self::getNodeValue($xpath, '//small[starts-with(text(), \'(scored by\')]'));
		$ranking = Strings::makeInteger(self::getNodeValue($xpath, '//span[starts-with(text(), \'Ranked\')]/following-sibling::node()[self::text()]'));
		$popularity = Strings::makeInteger(self::getNodeValue($xpath, '//span[starts-with(text(), \'Popularity\')]/following-sibling::node()[self::text()]'));
		$memberCount = Strings::makeInteger(self::getNodeValue($xpath, '//span[starts-with(text(), \'Members\')]/following-sibling::node()[self::text()]'));
		$favoriteCount = Strings::makeInteger(self::getNodeValue($xpath, '//span[starts-with(text(), \'Favorites\')]/following-sibling::node()[self::text()]'));

		//status
		$malStatus = strtolower(Strings::removeSpaces(self::getNodeValue($xpath, '//span[starts-with(text(), \'Status\')]/following-sibling::node()[self::text()]')));
		$status = Strings::makeEnum($malStatus, [
			'not yet published' => MediaStatus::NotYetPublished,
			'not yet aired'     => MediaStatus::NotYetPublished,
			'publishing'        => MediaStatus::Publishing,
			'currently airing'  => MediaStatus::Publishing,
			'finished'          => MediaStatus::Finished,
			'finished airing'   => MediaStatus::Finished,
		], null);
		if ($status === null)
			throw new BadProcessorDocumentException($doc, 'unknown status: ' . $malStatus);

		//air dates
		$publishedString = Strings::removeSpaces(self::getNodeValue($xpath, '//span[starts-with(text(), \'Aired\') or starts-with(text(), \'Published\')]/following-sibling::node()[self::text()]'));
		$pos = strrpos($publishedString, ' to ');
		if ($pos !== false)
		{
			$publishedFrom = Strings::makeDate(substr($publishedString, 0, $pos));
			$publishedTo = Strings::makeDate(substr($publishedString, $pos + 4));
		}
		else
		{
			$publishedFrom = Strings::makeDate($publishedString);
			$publishedTo = Strings::makeDate($publishedString);
		}

		$media = &$context->media;
		$media->media = $this->media;
		$media->title = $title;
		$media->sub_type = $subType;
		$media->picture_url = $pictureUrl;
		$media->average_score = $averageScore;
		$media->average_score_users = $averageScoreUsers;
		$media->publishing_status = $status;
		$media->popularity = $popularity;
		$media->members = $memberCount;
		$media->favorites = $favoriteCount;
		$media->ranking = $ranking;
		$media->published_from = $publishedFrom;
		$media->published_to = $publishedTo;
		$media->processed = date('Y-m-d H:i:s');
		R::store($media);
	}
}
