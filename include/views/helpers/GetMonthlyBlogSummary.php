<?php 
class Zend_View_Helper_GetMonthlyBlogSummary extends Zend_View_Helper_Abstract
{
	public function GetMonthlyBlogSummary($params) {
		
		$options = array();
		
		if (isset($params['id_utente'])) {
			$options['id_utente'] = (int) $params['id_utente'];			
		}
		
		if (isset($params['liveOnly']) && $params['liveOnly'])
			$options['status'] = DatabaseObject_BlogPost::STATUS_LIVE;
			
		$db = Zend_Registry::get('db');
		
		$summary = DatabaseObject_Blogpost::GetMonthlySummary($db, $options);
		
		if ($summary)
			return $summary;
			
		return false;
					
		
	}
}
?>