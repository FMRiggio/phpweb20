<?php
class DatabaseObject_BlogPostLocation extends DatabaseObject
{
	public function __construct($db)
	{
		parent::__construct($db, 'blog_post_luoghi', 'id_luogo');
		
		$this->add('id_post');
		$this->add('longitudine');
		$this->add('latitudine');
		$this->add('descrizione');
	}

	public function loadForPost($id_post, $id_luogo)
	{
		$id_post = (int)$id_post;
		$id_luogo = (int)$id_luogo;

		if ($id_post <= 0 || $id_luogo <= 0)
			return false;
			
		$query = sprintf('select %s from %s where id_post = %d and id_luogo = %d',
						 join(', ', $this->getSelectFields()),
						 $this->_table,
						 $id_post,
						 $id_luogo
						 );
		return $this->_load($query);
	}
	
	
	public static function GetLocations($db, $options = array())
	{
		
		// inizializza le opzioni
		$defaults = array('id_post' => array());
		
		foreach ($defaults as $k => $v) {
			$options[$k] = array_key_exists($k, $options) ? $options[$k] : $v;
		}
		
		$select = $db->select();
		$select->from(array('l' => 'blog_post_luoghi'), 'l.*');
		
		//filtra i risultati in base agli ID dei post specificati (se presenti)
		if (count($options['id_post']) > 0) {
			$select->where('l.id_post in (?)', $options['id_post']);
		}
		//acquisisce i dati del post dal db
		$data = $db->fetchAll($select);
		
		
		// trasforma i dati in un array di oggetti BlogPostLocation
		$location = parent::BuildMultiple($db, __CLASS__, $data);
		return $location;
	}

	public function __set($name, $value)
	{
		switch($name) {
			case 'latitudine':
			case 'longitudine':
				$value = sprintf('%01.6lf', $value);
				break;	
		}
		
		return parent::__set($name, $value);
	}
}
?>