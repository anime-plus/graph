<?php
class UserSubProcessorFriends extends UserSubProcessor
{
	const URL_FRIENDS = 0;

	public function getURLs($userName)
	{
		return
		[
			self::URL_FRIENDS => 'http://myanimelist.net/profile/' . $userName . '/friends',
		];
	}

	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_FRIENDS];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		Database::delete('userfriend', ['user_id' => $context->user->id]);
		$data = [];
		foreach ($xpath->query('//a[contains(@href, \'profile\')]/strong') as $node)
		{
			$friendName = Strings::removeSpaces($node->nodeValue);
			$data []= [
				'user_id' => $context->user->id,
				'name' => $friendName
			];
		}
		Database::insert('userfriend', $data);
	}
}
