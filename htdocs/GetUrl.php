<?php
class GetUrl extends Zend_Controller_Plugin_Abstract
{	
	
	public function __construct(){
	
	}
		
	public function getUrl($params = array()){
 		
 		$action = isset($params['action']) ? $params['action'] : null;
 		$controller = isset($params['controller']) ? $params['controller'] : null;
 		
 		$helper = new Zend_Controller_Action_Helper_Url;
 		$request = Zend_Controller_Front::getInstance()->getRequests;
 		
 		$url  = rtrim($request->getBaseUrl(), '/') . '/';
 		$url .= $helper->simple($action, $controller);
 		
 		return $url; 
	
	}
	
} 	
?>
