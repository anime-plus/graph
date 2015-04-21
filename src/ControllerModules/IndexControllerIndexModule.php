<?php
class IndexControllerIndexModule extends AbstractControllerModule
{
    public static function getUrlParts()
    {
        return ['', 'index'];
    }

    public static function url()
    {
        return '/';
    }

    public static function work(&$controllerContext, &$viewContext)
    {
        $viewContext->viewName = 'index-index';
        $viewContext->layoutName = 'layout-headerless';
        WebMediaHelper::addCustom($viewContext);
    }
}
