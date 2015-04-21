<?php
class MediaSubProcessorRelations extends MediaSubProcessor
{
	public function process(array $documents, &$context)
	{
		$doc = $documents[self::URL_MEDIA];
		$dom = self::getDOM($doc);
		$xpath = new DOMXPath($dom);

		Database::delete('mediarelation', ['media_id' => $context->media->id]);
		$data = [];
		$lastType = '';
		foreach ($xpath->query('//h2[starts-with(text(), \'Related\')]/../*') as $node)
		{
			if ($node->nodeName == 'h2' and (strpos($node->textContent, 'Related') === false or $node->textContent == 'Related Clubs'))
			{
				break;
			}
			if ($node->nodeName != 'a')
			{
				continue;
			}
			$link = $node->attributes->getNamedItem('href')->nodeValue;

			//relation type
			$malType = strtolower(Strings::removeSpaces($node->previousSibling->textContent));
			if ($malType == ',')
			{
				$type = $lastType;
			}
			else
			{
				$type = Strings::makeEnum($malType, [
					'sequel'              => MediaRelation::Sequel,
					'prequel'             => MediaRelation::Prequel,
					'side story'          => MediaRelation::SideStory,
					'parent story'        => MediaRelation::ParentStory,
					'adaptation'          => MediaRelation::Adaptation,
					'alternative version' => MediaRelation::AlternativeVersion,
					'summary'             => MediaRelation::Summary,
					'character'           => MediaRelation::Character,
					'spin-off'            => MediaRelation::SpinOff,
					'alternative setting' => MediaRelation::AlternativeSetting,
					'other'               => MediaRelation::Other,
					'full story'          => MediaRelation::FullStory,
				], null);
				if ($type === null)
					throw new BadProcessorDocumentException($doc, 'unknown relation type: ' . $malType);
				$lastType = $type;
			}

			//relation id
			preg_match_all('/([0-9]+)/', $link, $matches);
			if (!isset($matches[0][0]))
			{
				continue;
			}
			$mediaMalId = Strings::makeInteger($matches[0][0]);

			//relation media
			if (strpos($link, '/anime') !== false)
			{
				$media = Media::Anime;
			}
			elseif (strpos($link, '/manga') !== false)
			{
				$media = Media::Manga;
			}
			else
			{
				continue;
			}

			$data []= [
				'media_id' => $context->media->id,
				'mal_id' => $mediaMalId,
				'media' => $media,
				'type' => $type
			];
		}
		Database::insert('mediarelation', $data);
		$context->relationData = $data;
	}
}
