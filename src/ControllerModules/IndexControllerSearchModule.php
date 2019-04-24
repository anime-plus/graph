<?php
class IndexControllerSearchModule extends AbstractControllerModule
{
    public static function getUrlParts()
    {
        return ['s/search'];
    }
    
    public static function url()
    {
        return '/s/search';
    }
    
    public static function work(&$controllerContext, &$viewContext)
    {
        $username = !empty($_POST['user-name']) ? trim($_POST['user-name']) : '';
        
        if (empty($username)) {
            $viewContext->layoutName = null;
            
            $uri = IndexControllerIndexModule::url();
            
            HttpHeadersHelper::setCurrentHeader('Location', $uri);
            
            return;
        }
        
        if (!preg_match('#^' . UserController::getUserRegex() . '$#', $username)) {
            $viewContext->viewName = 'error-user-invalid';
            
            $viewContext->meta->noIndex = true;
            
            return;
        }
        
        $viewContext->layoutName = null;
        
        $uri = UserControllerProfileModule::url($username);
        
        HttpHeadersHelper::setCurrentHeader('Location', $uri . '?referral=search');
    }
}
