<?php
class Zend_View_Helper_GetTagSummary extends Zend_View_Helper_Abstract{
	
	public function GetTagSummary($params)
	{
		$db 	 = Zend_Registry::get('db');
		$id_utente = (int)$params['id_utente'];
		$summary = DatabaseObject_BlogPost::GetTagSummary($db, $id_utente);
		
		return $summary;
		
	}
	
}
?>