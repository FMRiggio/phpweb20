<?php
class DatabaseObject_BlogPost extends DatabaseObject
{
	public $profile = null;
	public $images = array();
    public $luoghi = array();
      
	const STATUS_DRAFT = 'D';
	const STATUS_LIVE = 'L';
        
	public function __construct($db)
	{
		parent::__construct($db, 'blog_posts', 'id_post');
		
		$this->add('id_utente');
		$this->add('url');
		$this->add('ts_creazione', time(), self::TYPE_TIMESTAMP);
		$this->add('status', self::STATUS_DRAFT);
		
		$this->profile = new Profile_BlogPost($db);
	}
		
	
    protected function preInsert(){
       	$this->url = $this->generateUniqueUrl($this->profile->title);
       	return true;
    }
        
    
	protected function postLoad()
    {
		$this->profile->setPostId($this->getId());
		$this->profile->load();
		$options = array('id_post' => $this->getId());
		$this->images = DatabaseObject_BlogPostImage::GetImages($this->getDb(), $options);
		
		$this->luoghi = DatabaseObject_BlogPostLocation::GetLocations($this->getDb(), $options);
	}

	
	protected function postInsert()
	{
		$this->profile->setPostId($this->getId());
		$this->profile->save(false);
		
		$this->addToIndex();
		return true;
	}

	
	protected function postUpdate()
	{
		$this->profile->save(false);
		$this->addToIndex();
		return true;
	}

	
	protected function preDelete()
	{
		$this->profile->delete();
		$this->deleteAllTags();
		foreach ($this->images as $image) {
			$image->delete(false);
		}
		$this->deleteFromIndex();
		return true;
	}

	
    public function loadForUser($id_utente, $id_post) {
        $idPost = (int) $id_post;
        $idUtente = (int) $id_utente;
        
        if ($idPost <= 0 || $idUtente<= 0) {
        	return false;
        }
        
        $query = sprintf('select %s from %s where id_utente = %d and id_post = %d',
        				 join(', ', $this->getSelectFields()),
        				 $this->_table,
        				 $idUtente,
        				 $idPost
        				 );
	
        return $this->_load($query);
    }

    
	public function sendLive(){
        if ($this->status != self::STATUS_LIVE) {
        	$this->status = self::STATUS_LIVE;
        	$this->profile->ts_pubblicato = time();
        }
	}

	
	public function isLive(){
	   	return $this->isSaved() && $this->status == self::STATUS_LIVE;
	}

	
	protected function generateUniqueUrl($title){
        $url = strtolower($title);
        $filters = array(
        			//sostituisce & con and 
        			'/&+/' => 'and',
        			//sostituisce i caratteri non alfanumerici con un trattino
        			'/[^a-z0-9]+/i' => '-',
        			//sostituisce più trattini con un singolo trattino
        			'/-+/' => '-'
        			);
        //applico i filtri
        foreach ($filters as $regex => $replacement) {
        	$url = preg_replace($regex, $replacement, $url);
        }
        
        //rimuove trattini a inizio e fine stringa
        $url = trim($url, '-');
        
        //limite sulla lunghezza dell'url
        $url = trim(substr($url, 0, 50));
        
        if (strlen($url) == 0) {
        	$url = 'post';
        }
        
        //eventuali url simili
        $query = sprintf("select url from %s where id_utente = %d and url like ?",
        				 $this->_table,
        				 $this->id_utente);
        $query = $this->_db->quoteInto($query, $url . '%');
        $result = $this->_db->fetchCol($query);
        
        // se è unico resta questo
        if (count($result) == 0 || !in_array($url, $result))
        	return $url;
        
        //genera un URL univoco
        $i = 2;
        do {
        	$url = $url . '-' . $i++;
        } while (in_array($url, $result));
        
        return $url;
	}

	
	public function sendBackToDraft()
	{        	
		$this->status = self::STATUS_DRAFT;	
	}

	
    private static function _GetBaseQuery($db, $options)
    {
        // inizializza le opzioni
        $defaults = array(
        	'id_utente' 	=> array(),
        	'status'		=> '',
        	'public_only' 	=> false,
        	'tag'			=> '',
        	'from'	  		=> '',
        	'to'	  		=> ''
        );        	
        
        foreach ($defaults as $k => $v) {
        	$options[$k] = array_key_exists($k, $options) ? $options[$k] : $v;        		
        }
        
        $select = $db->select();
        $select->from(array('p' => 'blog_posts'), array());
        
        //filtro sulle date
        if (strlen($options['from']) > 0) {
        	$ts = strtotime($options['from']);
        	$select->where('p.ts_creazione >= ?', date('Y-m-d H:i:s', $ts));	
        }
        
        if (strlen($options['to']) > 0) {
        	$ts = strtotime($options['to']);
        	$select->where('p.ts_creazione <= ?', date('Y-m-d H:i:s', $ts));
        }
        
        //filtra i risultati in base agli id utente passati
        if (count($options['id_utente']) > 0) {
        	$select->where('p.id_utente in (?)', $options['id_utente']);
        }
        
        if ($options['public_only']) {
        	$select->joinInner(array('up' => 'profilo_utenti'),
        					   'p.id_utente = up.id_utente',
        					    array())
        		   ->where("chiave_profilo = 'blog_public'")
        		   ->where("valore_profilo = '1'");	
        }
        
        //filtro in base allo stato del post
        if (strlen($options['status']) > 0) 
        	$select->where('status = ?', $options['status']);

        //seleziono i post in base al tag
        $options['tag'] = trim($options['tag']);
        if (strlen($options['tag']) > 0) {
        	$select->joinInner(array('t'   => 'blog_post_tag'), 't.id_post = p.id_post', array())
        		   ->where('lower(t.tag) = lower(?)', $options['tag']);
        }
        		
        return $select;
    }

    
    public static function GetPostsCount($db, $options) 
    {
        $select = self::_GetBaseQuery($db, $options);
        $select->from(null, 'count(*)');	
        return $db->fetchOne($select);
    }

    
	public static function GetPosts($db, $options = array())
    {
        //inizializza le opzioni
        $defaults = array(
        	'offset' => 0,
        	'limit'	 => 0,
        	'order'	 => 'p.ts_creazione'
        );
        
        foreach ($defaults as $k => $v) {
        	$options[$k] = array_key_exists($k, $options) ? $options[$k] : $v;
        }
        
        $select = self::_GetBaseQuery($db, $options);
        
        //imposto i campi da selezionare
        $select->from(null, 'p.*');
        
        if ($options['limit'] > 0) {
        	$select->limit($options['limit'], $options['offset']);
        }
        
        $select->order($options['order']);
        
        $data = $db->fetchAll($select);
        
        $posts = self::BuildMultiple($db, __CLASS__, $data);
        $posts_ids = array_keys($posts);
        
        if (count($posts_ids) == 0)
        	return array();
        	
        // carico i dati del profilo per i post caricati
        $profiles = Profile::BuildMultiple($db, 'Profile_BlogPost', array('id_post' => $posts_ids));
        
        foreach ($posts as $id_post => $post) {
        	if (array_key_exists($id_post, $profiles) && $profiles[$id_post] instanceof Profile_BlogPost) {
        		$posts[$id_post]->profile = $profiles[$id_post];         			
        	} else {
        		$posts[$id_post]->profile->setPostId($id_post);
        	}    		
        }
		
        //carica le immagini per ogni post
        $options = array('id_post' => $posts_ids);
        
        $images  = DatabaseObject_BlogPostImage::GetImages($db, $options);
        
        foreach ($images as $image) {
        	$posts[$image->id_post]->images[$image->getId()] = $image;
        }
        
        $locations = DatabaseObject_BlogPostLocation::GetLocations($db, $options);
        
        foreach ($locations as $l) {
        	$posts[$l->id_post]->luoghi[$l->getId()] = $l;
        }
        return $posts;
    }

    
	public static function GetMonthlySummary($db, $options) 
	{
		if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
			$datestring = "date_format(p.ts_creazione, '%Y-%m')";
		} else {
			$datestring = "to_char(p.ts_creazione, 'yyyy-mm')";
		}
		
		$defaults = array(
			'offset' => 0,
			'limit'	 => 0,
			'order'	 => $datestring . ' DESC'
		);
		
		foreach ($defaults as $k => $v) {
			$options[$k] = array_key_exists($k , $options) ? $options[$k] : $v;
		}
		
		$select = self::_GetBaseQuery($db, $options);
		$select->from(null, array($datestring . 'as month', 'count(*) as num_posts'));
		$select->group($datestring);
		$select->order($options['order']);
		
		return $db->fetchPairs($select);
	}
        
		
	public function getTeaser($length) {
		$string = strip_tags($this->profile->content);
		return substr($string, 0, $length);
	}
	
	
	public function loadLivePost($idUtente, $url)
	{
		$idUtente 	= (int) $idUtente;
		$url		= trim($url);

		if ($idUtente <= 0 || strlen($url) == 0)
			return false;
			
		$select = $this->_db->select();

		$select->from($this->_table, $this->getSelectFields())
			   ->where('id_utente = ?', $idUtente)
			   ->where('url = ?', $url)
			   ->where('status = ?', self::STATUS_LIVE);

		return $this->_load($select);
	}

	
	public function getTags()
	{
		if (!$this->isSaved())
			return array();
			
		$query  = 'select tag from blog_post_tag where id_post = ?';
		$query .= ' order by tag';
		
		return $this->_db->fetchCol($query, $this->getId());
	}
	
	
	public function hasTag($tag)
	{
		if (!$this->isSaved())
			return array();

		$select = $this->_db->select();
		$select->from('blog_post_tag', 'count(*)')
			   ->where('id_post = ?', $this->getId())
			   ->where('lower(tag) = lower(?)', trim($tag));
			   
		return $this->_d->fetchOne($select) > 0;
	}

	
	public function addTags($tags)
	{
		if (!$this->isSaved())
			return array();

		if (!is_array($tags))
			$tags = array($tags);

		//creo una lista pulita di tag
		$_tags = array();
		foreach ($tags as $tag) {
			$tag = trim($tag);
			if (strlen($tag) == 0)
				continue;
			
			$_tags[strtolower($tag)] = $tag;	
		}	
		
		//inserisco ogni tag nel db che non sia già esistente
		$existingTags = array_map('strtolower', $this->getTags());
		
		foreach ($_tags as $lower => $tag) {
			if (in_array($lower, $existingTags))
				continue;
			$data = array('id_post' => $this->getId(), 'tag' => $tag);
			$this->_db->insert('blog_post_tag', $data);
		}
		
		$this->addToIndex();
	}
	
	
	public function deleteTags($tags)
	{
		if (!$this->isSaved())
			return array();

		if (!is_array($tags))
			$tags = array($tags);

		$_tags = array();
		foreach ($tags as $tag) {
			$tag = trim($tag);
			if (strlen($tag) > 0)
				$_tags[] = strtolower($tag);	
		}

		if (count($_tags) == 0) {
			return;
		}
		
		$where = array('id_post = ' . $this->getId(), $this->_db->quoteInto('lower(tag) in (?)', $tags));
		
		$this->_db->delete('blog_post_tag', $where);
		
		$this->addToIndex();
	}
    	
	
	public function deleteAllTags($tags)
	{
		if (!$this->isSaved())
			return array();
			
		$this->_db_delete('blog_post_tag', 'id_post = ' . $this->getId());
	}		

	
	public function GetTagSummary($db, $user_id)
	{
		
		$select = $db->select();
		$select->from(array('t' => 'blog_post_tag'), array('count(*) as count', 't.tag'))
			   ->joinInner(array('p' => 'blog_posts'), 'p.id_post = t.id_post', array())
			   ->where('p.id_utente = ?', $user_id)
			   ->where('p.status = ?',self::STATUS_LIVE)
			   ->group('t.tag');
		$result = $db->query($select);
		$tags 	= $result->fetchAll();
		
		$summary = array();
		
		foreach ($tags as $tag) {
			$_tag = strtolower($tag['tag']);
			if (array_key_exists($_tag, $summary))
				$summary[$_tag]['count'] += $tag['count'];
			else
				$summary[$_tag] = $tag;
		}
		return $summary;
	}

	public function setImageOrder($order)
	{
		// pulisco gli id delle immagini
		if (!is_array($order))
			return;
		
		$newOrder = array();
		foreach ($order as $id_immagine) {
			if (array_key_exists($id_immagine, $this->images))
				$newOrder[] = $id_immagine;
		}
		
		//controllo la lunghezza degli array
		$newOrder = array_unique($newOrder);
		if (count($newOrder) != count($this->images)) {
			return;
		}
		
		// aggiorna il database
		$rank = 1;
		foreach ($newOrder as $id_immagine) {
			$this->_db->update( 'blog_post_immagini',
								array('ranking' => $rank),
								'id_immagine = ' .$id_immagine);
			$rank++;
		}
		
	}
	
	
	public function getIndexableDocument()
	{
		$doc = new Zend_Search_Lucene_Document();
		$doc->addField(Zend_Search_Lucene_Field::keyword('id_post', $this->getId()));
		
		$fields = array(
			'title'		=> $this->profile->title,
			'content'	=> $this->profile->content,
			'published'	=> $this->profile->ts_pubblicato,
			'tags'		=> join(' ', $this->getTags())
		);
		
		foreach ($fields as $name => $field) {
			$doc->addField( Zend_Search_Lucene_Field::unStored($name, $field));
		}
		
		return $doc;
	}
	
	
	public static function getIndexFullPath() 
	{
		$config = Zend_Registry::get('config');
		
		return sprintf('%s/search-index', $config->paths->data);
	}
	
	public static function RebuildIndex()
	{
		try {
			$index = Zend_Search_Lucene::create(self::getIndexFullPath());
			
			$options = array('status' => self::STATUS_LIVE);
			$posts = self::GetPosts(Zend_Registry::get('db'), $options);
			
			foreach ($posts as $post) {
				$index->addDocument($post->getIndexableDocument());
			}
			$index->commit();
		} catch(Exception $ex) {
			$logger = Zend_Registry::get('logger');
			$logger->warn('Errore di ricostruzione indice di ricerca ' . $ex->getMessage());
		}
	}
	
	
	protected function addToIndex()
	{
		try {
			$index = Zend_Search_Lucene::open(self::getIndexFullPath());
		} 
		catch (Exception $ex) {
			self::RebuildIndex();
			return;
		}
		
		try {
			$query = new Zend_Search_Lucene_Search_Query_Term(
				new Zend_Search_Lucene_Index_Term($this->getId(), 'id_post')
			);
			$hits = $index->find($query);
			foreach ($hits as $hit) {
				$index->delete($hit->id);
			}
			if ($this->status == self::STATUS_LIVE) {
				$index->addDocument($this->getIndexableDocument());
			}
			$index->commit();
		} 
		catch (Exception $ex) {
			$logger = Zend_Registry::get('logger');
			$logger->warn('Errore aggiornamento documento indice di ricerca: ' . $ex->getMessage());
		}
	}
	
	protected function deleteFromIndex()
	{
		try {
			$index = Zend_Search_Lucene::open(self::getIndexFullPath());
			
			$query = new Zend_Search_Lucene_Search_Query_Term(
				new Zend_Search_Lucene_Index_Term($this->getid(), 'id_post')
			);
			
			$hits = $index->find($query);
			foreach ($hits as $hit) {
				$index->delete($hit->id);
			}
			$index->commit();
		}
		catch (Exception $ex) {
			$logger = Zend_Registry::get('logger');
			$logger->warn('Errore rimozione documento da indice di ricerca: ' . $ex->getMessage());			
		}
		
	}
	
	
	public static function GetTagSuggestions($db, $partialTag, $limit = 0)
	{
		
		$partialTag = trim($partialTag);
		if (strlen($partialTag) == 0)
			return array();

		$select = $db->select();
		$select->distinct();
		$select->from(array('t' => 'blog_post_tag'), 'tag')
			   ->joinInner(array('p' => 'blog_posts'), 't.id_post = p.id_post', array())
			   ->where('t.tag ilike ?', $partialTag . '%')
			   ->where('p.status = ?', self::STATUS_LIVE)
			   ->order('tag');
		
		if ($limit > 0) {
			$select->limit($limit);	
		}
		return $db->fetchCol($select);
	}
}
?>