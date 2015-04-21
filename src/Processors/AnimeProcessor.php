<?php
class AnimeProcessor extends AbstractProcessor
{
	public function beforeProcessing(&$context)
	{
		$media = R::findOne('media', 'mal_id = ? AND media = ?', [$context->key, Media::Anime]);
		if (empty($media))
		{
			$media = R::dispense('media');
			$media->mal_id = $context->key;
			$media->media = Media::Anime;
			R::store($media);
		}
		$context->media = $media;
	}

	public function getSubProcessors()
	{
		$subProcessors = [];
		$subProcessors []= new MediaSubProcessorBasic(Media::Anime);
		$subProcessors []= new MediaSubProcessorGenres(Media::Anime);
		$subProcessors []= new MediaSubProcessorTags(Media::Anime);
		$subProcessors []= new MediaSubProcessorRelations(Media::Anime);
		$subProcessors []= new MediaSubProcessorFranchises(Media::Anime);
		$subProcessors []= new MediaSubProcessorRecommendations(Media::Anime);
		$subProcessors []= new AnimeSubProcessorBasic();
		$subProcessors []= new AnimeSubProcessorProducers();
		return $subProcessors;
	}

	public function onProcessingError(&$context)
	{
		if ($context->exception instanceof BadProcessorKeyException)
		{
			Database::delete('mediagenre', ['media_id' => $context->media->id]);
			Database::delete('mediatag', ['media_id' => $context->media->id]);
			Database::delete('mediarelation', ['media_id' => $context->media->id]);
			Database::delete('mediarec', ['media_id' => $context->media->id]);
			Database::delete('animeproducer', ['media_id' => $context->media->id]);
			Database::delete('media', ['id' => $context->media->id]);
		}
		throw $context->exception;
	}
}
