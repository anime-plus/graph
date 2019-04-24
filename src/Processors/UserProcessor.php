<?php
class UserProcessor extends AbstractProcessor
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
		$subProcessors = [];
		$subProcessors []= new UserSubProcessorProfile();
		//$subProcessors []= new UserSubProcessorFriends();
		$subProcessors []= new UserSubProcessorHistory();
		//$subProcessors []= new UserSubProcessorUserMedia();
		return $subProcessors;
	}

	public function onProcessingError(&$context)
	{
		if ($context->exception instanceof BadProcessorKeyException)
		{
			Database::delete('userfriend', ['user_id' => $context->user->id]);
			Database::delete('userhistory', ['user_id' => $context->user->id]);
			Database::delete('usermedia', ['user_id' => $context->user->id]);
			Database::delete('user', ['id' => $context->user->id]);
		}
		throw $context->exception;
	}
}
