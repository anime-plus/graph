<?php
class UserMediaProcessor extends AbstractProcessor
{
    public function beforeProcessing(&$context)
	{
		Database::selectUser($context->key);
        
		$user = R::findOne('user', 'LOWER(name) = LOWER(?)', [$context->key]);
        
		if (empty($user))
		{
			$user = R::dispense('user');
            
			$user->name = $context->key;
            
			R::store($user);
		}
        
		$context->user = $user;
	}
    
	public function getSubProcessors($key)
	{
		$userMediaSubProcessorBasic = new UserMediaSubProcessorBasic();
        
        Database::selectUser($key);
        
		$user = R::findOne('user', 'LOWER(name) = LOWER(?)', [$key]);
        
		if (!empty($user))
		{
            $userMediaSubProcessorBasic->setAnimeRange($user->anime_days_spent ? ceil($user->anime_days_spent) : 0);
            
            $userMediaSubProcessorBasic->setMangaRange($user->manga_days_spent ? ceil($user->manga_days_spent) : 0);
		}
        
		$subProcessors[] = $userMediaSubProcessorBasic;
        
		return $subProcessors;
	}
    
	public function onProcessingError(&$context)
	{
		if ($context->exception instanceof BadProcessorKeyException)
		{
			Database::delete('usermedia', ['user_id' => $context->user->id]);
		}
        
		throw $context->exception;
	}
}
