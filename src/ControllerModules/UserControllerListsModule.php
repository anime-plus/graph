<?php
class UserControllerListsModule extends AbstractUserControllerModule
{
    public static function getText(ViewContext $viewContext, $media)
    {
        return ucfirst(Media::toString($media) . ' list');
    }

    public static function getUrlParts()
    {
        return ['list'];
    }

    public static function getMediaAvailability()
    {
        return [Media::Anime, Media::Manga];
    }

    public static function getOrder()
    {
        return 1;
    }

    public static function work(&$controllerContext, &$viewContext)
    {
        $viewContext->viewName = 'user-list';
        $viewContext->meta->title = $viewContext->user->name . ' &#8212; List (' . Media::toString($viewContext->media) . ') &#8212; ' . Config::$title;
        $viewContext->meta->description = $viewContext->user->name . '\'s ' . Media::toString($viewContext->media) . ' list.';
        WebMediaHelper::addTablesorter($viewContext);
        WebMediaHelper::addCustom($viewContext);
        
        $viewContext->list = $viewContext->user->getMixedUserMedia($viewContext->media);
        $viewContext->private = $viewContext->user->isUserMediaPrivate($viewContext->media);
    }
}
