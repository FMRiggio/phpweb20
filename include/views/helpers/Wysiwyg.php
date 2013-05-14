<?php
class Zend_View_Helper_Wysiwyg extends Zend_View_Helper_Abstract
{
	public function wysiwyg($params){
		
		$name = '';
		$value = '';
		
		if (isset($params['name'])) 
			$name = $params['name'];
		
		if (isset($params['value']))
			$value = $params['value'];
			
		$fckeditor = new FCKeditor($name);
		$fckeditor->BasePath = '/js/fckeditor/';
		$fckeditor->ToolbarSet = 'phpweb20';
		$fckeditor->Value = $value;

		return $fckeditor->CreateHtml();
		
	}
	
}

?>