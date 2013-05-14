<?php
class Zend_View_Helper_ImageFileName extends Zend_View_Helper_Abstract
{
	public function ImageFileName($params){
		
		if (!isset($params['id']))
			$params['id'] = 0;
		
		if (!isset($params['w']))
			$params['w'] = 0;

		if (!isset($params['h']))
			$params['h'] = 0;

		$geturlHelper = new Zend_View_Helper_Geturl();

		$hash = DatabaseObject_BlogPostImage::GetImageHash(
			$params['id'],
			$params['w'],
			$params['h']
		);
		
		$options = array('controller' => 'utility', 'action' => 'image');
		
		return sprintf('%s?id=%d&w=%d&h=%d&hash=%s', 
						$geturlHelper->geturl($options), 
						$params['id'],
						$params['w'],
						$params['h'],
						$hash);
		
	}
	
}

?>