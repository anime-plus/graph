<?php
class AdminControllerMiscModule extends AbstractControllerModule
{
	public static function getUrlParts()
	{
		return ['a/misc'];
	}

	public static function url()
	{
		return '/a/misc';
	}

	public static function work(&$controllerContext, &$viewContext)
	{
		try
		{
			if (empty($_POST['action']))
			{
				throw new Exception('No action specified');
			}
			$action = $_POST['action'];


			if ($action == 'wipe-cache')
			{
				$deleted = 0;
				foreach ($controllerContext->cache->getAllFiles() as $path)
				{
					$deleted ++;
					unlink($path);
				}
				$viewContext->messageType = 'info';
				$viewContext->message = 'Deleted ' . $deleted . ' files';
			}

			else
			{
				throw new Exception('Unknown action: ' . $action);
			}
		}
		catch (Exception $e)
		{
			$viewContext->messageType = 'error';
			$viewContext->message = $e->getMessage();
		}

		$viewContext->viewName = 'admin-index';
		$viewContext->meta->title = 'Admin - ' . Config::$title;
		WebMediaHelper::addCustom($viewContext);
	}
}
