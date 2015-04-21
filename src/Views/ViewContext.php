<?php
class ViewContext
{
    public $name;

    public function __construct()
    {
        $this->layoutName = 'layout';
        $this->viewName = null;
        $this->renderStart = microtime(true);
        $this->meta = new StdClass;
        $this->meta->description = Config::$title . ' (MALgraph) is an extension of your myanimelist.net profile, showing you various information about your anime and manga.';
        $this->meta->styles = [];
        $this->meta->scripts = [];
        WebMediaHelper::addBasic($this);
    }
}
