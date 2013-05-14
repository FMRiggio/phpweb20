<?php
class UserController extends CustomControllerAction
{
	protected $user = null;
	
    public function preDispatch() {
   		parent::preDispatch();
   		
   		$request = $this->getRequest();
   		
   		if (strtolower($request->getActionName()) == 'user-not-found') {
   			return ;
   		} 
   		
   		//recupero l'username
   		$username = trim($request->getUserParam('username'));
   		
   		//se username è vuoto redirect a user not found
   		if (strlen($username) == 0) {
   			$this->_redirect($this->getUrl('index', 'index'));
   		}
   		
   		$this->user = new DatabaseObject_User($this->db);
   		if (!$this->user->loadByUsername($username)) { 
   			$this->_forward('user-not-found');
   			return ;
   		}
   		$this->breadcrumbs->addStep('Blog di ' . $this->user->username, $this->getCustomUrl(array('username' => $this->user->username,
   																								  'action' => 'index'), 'user'));
   		$this->view->user = $this->user;
   	}
    	
    public function indexAction()
    {
    	
		if (isset($this->user->profile->num_posts))
			$limit = max(1, (int) $this->user->profile->num_posts);
		else
			$limit = 10;

		$options = array(
			'id_user' 	=> $this->user->getId(),
			'status'	=> DatabaseObject_BlogPost::STATUS_LIVE,
			'limit'		=> $limit,
			'order'		=> 'p.ts_creazione desc'
		);
		
		$posts = DatabaseObject_BlogPost::GetPosts($this->db, $options);
		$this->view->posts  = $posts;
    }
	
    public function userNotFoundAction()
    {
    	$username = trim($this->getRequest()->getUserParam('username'));
    	
    	$this->breadcrumbs->addStep('User Not Found');
    	$this->view->requestedUsername = $username;
    	
    }
    
	public function viewAction()
    {
		$request = $this->getRequest();
		$url = trim($request->getUserParam('url'));
		
		//se url è vuoto torno a homepage
		if (strlen($url) == 0) {
			$this->_redirect($this->getCustomUrl(
				array('username' => $this->user->username,
					  'action'	 => 'index'),
				'user'
			));
		}
		
		//cerca e carica il post
		$post = new DatabaseObject_BlogPost($this->db);
		$post->loadLivePost($this->user->getId(), $url);
		
		//se il post non è stato caricato passa a postNotFound
		if (!$post->isSaved()) {
			$this->_forward('post-not-found');
			return ;
		}
		
		// crea opzioni per il link ai breadcrumb dell'archivio
		$archiveOptions = array(
			'username' 	=> $this->user->username,
			'year'		=> date('Y', $post->ts_creazione),
			'month'		=> date('m', $post->ts_creazione)
		);
		
		$this->breadcrumbs->addStep(
			date('F Y', $post->ts_creazione),
			$this->getCustomUrl($archiveOptions, 'archive')
		);
    	$this->breadcrumbs->addStep($post->profile->title);
    	
    	//passa il post alla view
    	$this->view->post = $post;
    	
    }
    
	public function postNotFoundAction()
    {
		$this->breadcrumbs->addStep('Post non trovato');
    }
    
	public function archiveAction()
    {
		$request = $this->getRequest();

		// prendo data e mese dalle variabili
		$m = (int) trim($request->getUserParam('month'));
		$y = (int) trim($request->getUserParam('year'));
		
		//controllo il mese
		$from = mktime(0, 0, 0, $m,     1, $y);
		$to   = mktime(0, 0, 0, $m + 1, 1, $y) - 1;
		
		// recupera i post pubblicati in base a data e ora e prendo i post più recenti
		$options = array(
			'id_utente' => $this->user->getId(),
			'from'		=> date('Y-m-d H:i:s', $from),
			'to'		=> date('Y-n-d H:i:s', $to),
			'status'	=> DatabaseObject_BlogPost::STATUS_LIVE,
			'order'		=> 'p.ts_creazione DESC'
		);
		
		$posts = DatabaseObject_BlogPost::GetPosts($this->db, $options);
		
		$this->breadcrumbs->addStep(date('F Y', $from));
		
		//passo mese e anno alla view
		$this->view->month = $from;
		$this->view->posts = $posts;
		
    }
    
	public function tagAction()
    {
		$request = $this->getRequest();
		
		$tag = trim($request->getUserParam('tag'));
		
		if (strlen($tag) == 0) {
			$this->_redirect($this->getCustomUrl(
				array('username' => $this->user->username,
					  'action'	 => 'index')
			));
		}
		
		// recupera i post pubblicati in base a data e ora e prendo i post più recenti
		$options = array(
			'id_utente' => $this->user->getId(),
			'tag'		=> $tag,
			'status'	=> DatabaseObject_BlogPost::STATUS_LIVE,
			'order'		=> 'p.ts_creazione DESC'
		);
		
		$posts = DatabaseObject_BlogPost::GetPosts($this->db, $options);
		
		$this->breadcrumbs->addStep('Tag ' . $tag);
		//passo mese e anno alla view
		$this->view->tag = $tag;
		$this->view->posts = $posts;
		
    }   
    
    public function feedAction()
    {
    	//recupero i post recenti
		$options = array(
			'id_utente' => $this->user->getId(),
			'status'	=> DatabaseObject_BlogPost::STATUS_LIVE,
			'limit'		=> 10,
			'order'		=> 'p.ts_creazione DESC'
		);    	
		
		$recentPosts = DatabaseObject_BlogPost::GetPosts($this->db, $options);
		
		//URL di base dei link generati
		$domain = 'http://' . $this->getRequest()->getServer('HTTP_HOST');
		
		//URL del feed
		$url = $this->getCustomUrl(array('username' => $this->user->username,
					 					 'action'	 => 'index'),'user');
		$feedData = array(
			'title' 	=> sprintf("Blog di %s", $this->user->username),
			'link'  	=> $domain . $url,
			'charset' 	=> 'UTF-8',
			'entries' 	=> array()
		);
		
		//crea le voci del feed in base ai post restituiti
		foreach ($recentPosts as $post) {
			$url = $this->getCustomUrl(
				array('username' => $this->user->username,
				  	  'url'		 => $post->url), 'post');
			
			$entry = array(
				'title'		  => $post->profile->title,
				'link'		  => $domain . $url,
				'description' => $post->getTeaser(200),
				'lastUpdate'  => $post->ts_creazione,
				'category'    => array() 
			);
			
			//assegna i tag a ogni voce
			foreach ($post->getTags() as $tag) {
				$entry['category[]'] = array('term' => $tag);
			}
			$feedData['entries'][] = $entry;
			
			//creo il feed
			$feed = Zend_Feed::importArray($feedData, 'atom');
			
			//disattivo il rendering automatico
			$this->_helper->viewRenderer->setNoRender();
			
			//genera il feed per il browser
			$feed->send();
			
		}
    }
}
?>