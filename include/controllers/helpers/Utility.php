<?php
/*
 * Created on 15/ott/10
 * 
 * UtilityController - Controller con metodi di utilità
 */
 
class Zend_Controller_Action_Helper_Utility extends Zend_Controller_Action_Helper_Abstract
{
	
	public function captcha(){
		
		$session = new Zend_Session_Namespace('captcha');
		$view = new Zend_View();
				
		$opts = array('name' => 'capt',
					  'wordLen' => 6,
					  'timeout' => 300);
					 
		$captcha = new Zend_Captcha_Image($opts);		
		$captcha->setFont(Zend_Registry::get('config')->paths->data . '/VeraBd.ttf');
		$captcha->setFontSize(20);		
		$captcha->setWidth(150);
		$captcha->setHeight(60);			
										
		$idCaptcha = $captcha->generate();
		$session->phrase = $captcha->getWord();
		
		return $captcha->render($view);			
	 	
		
	}
} 
?>
