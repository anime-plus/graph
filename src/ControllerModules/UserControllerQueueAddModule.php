<?php
class UserControllerQueueAddModule extends AbstractUserControllerModule
{
	public static function getUrlParts()
	{
		return ['queue-add'];
	}

	public static function getMediaAvailability()
	{
		return [];
	}

	public static function preWork(&$controllerContext, &$viewContext)
	{
		parent::preWork($controllerContext, $viewContext);
		$controllerContext->cache->bypass(true);
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		$queue = new Queue(Config::$userQueuePath);
        $queueMedia = new Queue(Config::$userMediaQueuePath);
		$queueItem = new QueueItem(strtolower($controllerContext->userName));
		$user = R::findOne('user', 'LOWER(name) = LOWER(?)', [$controllerContext->userName]);
		$profileAge = (time() - (isset($user->processed) ? strtotime($user->processed) : 0));
		$banned = BanHelper::getUserBanState($controllerContext->userName) != BanHelper::USER_BAN_NONE;
        if ($profileAge > Config::$userQueueMinWait && !$banned)
        {
			$queue->enqueue($queueItem);
            $queueMedia->enqueue($queueItem);
        }

		$j['user'] = $controllerContext->userName;
		$j['pos'] = $queue->seek($queueItem);

		$viewContext->layoutName = 'layout-json';
		$viewContext->json = $j;
	}
}
