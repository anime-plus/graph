<?php
class AdminControllerLoginModule extends AbstractControllerModule
{
    public static function getUrlParts()
    {
        return ['a/login'];
    }
    
    public static function url()
    {
        return '/a/login';
    }
    
    public static function work(&$controllerContext, &$viewContext)
    {
        $viewContext->viewName = 'admin-login';
        
        $viewContext->meta->title = 'Admin &#8212; ' . Config::$title;
        
        WebMediaHelper::addCustom($viewContext);
        
        if (isset($_POST['password'])) {
            $viewContext->entered = $_POST['password'];
            
            if ($_POST['password'] === Config::$adminPassword) {
                $_SESSION['logged-in'] = $_POST['password'];
            }
            
            if (isset($_SESSION['logged-in'])) {
                $uri = AdminControllerIndexModule::url();
                
                $viewContext->viewName = null;
                
                HttpHeadersHelper::setCurrentHeader('Location', $uri);
            }
        }
    }
}
