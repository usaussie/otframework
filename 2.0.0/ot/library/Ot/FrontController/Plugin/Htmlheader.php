<?php
/**
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * This license is also available via the world-wide-web at
 * http://itdapps.ncsu.edu/bsd.txt
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to itappdev@ncsu.edu so we can send you a copy immediately.
 *
 * @package    Ot_FrontController_Plugin_Htmlheader
 * @category   Front Controller Plugin
 * @copyright  Copyright (c) 2007 NC State University Office of Information Technology
 * @license    http://itdapps.ncsu.edu/bsd.txt  BSD License
 * @version    SVN: $Id: $
 */

/**
 * Allows the application to automatically load CSS and Javascript files that are
 * associated with the dispatched module, controller and action.
 *
 * @package    Ot_FrontController_Plugin_Htmlheader
 * @category   Front Controller Plugin
 * @copyright  Copyright (c) 2007 NC State University Office of Information Technology
 */
class Ot_FrontController_Plugin_Htmlheader extends Zend_Controller_Plugin_Abstract
{
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $view = Zend_Layout::getMvcInstance()->getView();
            	
        $baseUrl = $view->baseUrl();
        
        $css = array();
        $javascript = array();
        
        // check application directories and append to existing array
        $javascript = $this->_autoload($baseUrl, './public/scripts', 'js', $request, $javascript);   
        $css        = $this->_autoload($baseUrl, './public/css', 'css', $request, $css);
        
        // check OT directories and append to existing array
        $javascript = $this->_autoload($baseUrl, './public/ot/scripts', 'js', $request, $javascript);
        $css        = $this->_autoload($baseUrl, './public/ot/css', 'css', $request, $css);
        
        foreach ($css as $c) {
            $view->headLink()->appendStylesheet($c);
        }
        
        foreach ($javascript as $j) {
            $view->headScript()->appendFile($j);
        }       
    }
    
    protected function _autoload($baseUrl, $directory, $extension, $request, $existing)
    {
        $req = array(
            'module'     => $request->getModuleName(),
            'controller' => $request->getControllerName(),
            'action'     => strtolower($request->getActionName()),
        );

        $path = '';
        
        foreach ($req as $r) {
            $path .= (($path == '') ? '' : '/') . $r; 
            
            $autoload = $path . '.' . $extension;
        
	        if (is_file($directory . '/' . $autoload)) {
	        	
	        	$file = str_replace('./', $baseUrl . '/', $directory . '/' . $autoload);
	        	
	             if (is_array($existing)) {
	                array_push($existing, $file);        
	             } else {
	                if ($existing != '') {
	                    $existing = array($existing, $file);
	                } else {
	                    $existing = array($file);
	                }
	             }
	        }
        }
        
        return $existing;     	
    }
}