<?php
class BlogmanagerController extends CustomControllerAction
{
	public function init(){
		parent::init();
		$this->breadcrumbs->addStep('Account', $this->getUrl(null, 'account'));
		$this->breadcrumbs->addStep('Blog Manager', $this->getUrl(null, 'blogmanager'));
		
		$this->identity = Zend_Auth::getInstance()->getIdentity();
	}
	
	public function indexAction()
	{
		// prendo il mese
		$month = $this->getRequest()->getQuery('month');
		if (preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
			$y = $matches[1];
			$m = max(1, min(12, $matches[2]));
		} else {
			$y = date('Y');
			$m = date('n');
		}
		
		$from = mktime(0, 0, 0, $m,     1, $y);
		$to   = mktime(0, 0, 0, $m + 1, 1, $y); 
		
		$options = array(
			'id_utente' => $this->identity->id_utente,
			'from'		=> date('Y-m-d H:i:s', $from),
			'to'		=> date('Y-m-d H:i:s', $to),
			'order'		=> 'p.ts_creazione DESC'
		);
		
		$recentPosts = DatabaseObject_BlogPost::GetPosts($this->db, $options);
		
		// numero totale dei post per utente
		$totalPosts = DatabaseObject_BlogPost::GetPostsCount($this->db, array('id_utente' => $this->identity->id_utente));
		
		$this->view->month = $from;
		$this->view->recentPosts = $recentPosts;
		$this->view->totalPosts = $totalPosts;
	}
	
	public function editAction(){
		$request = $this->getRequest();

		$id_post = (int) $this->getRequest()->getQuery('id');
		
		$fp = new FormProcessor_BlogPost($this->db, $this->identity->id_utente, $id_post);
		
		if ($request->isPost()) {
			if ($fp->process($request)) {
				$url = $this->getUrl('preview') . '?id=' . $fp->post->getId();
				$this->_redirect($url);
			}
		}
		
		if ($fp->post->isSaved()) {
			$this->breadcrumbs->addStep('Anteprima Post: ' . $fp->post->profile->title, $this->getUrl('preview') . '?id=' . $fp->post->getId());
			$this->breadcrumbs->addStep('Modifica Post del Blog');
		} else {
			$this->breadcrumbs->addStep('Crea un nuovo post del blog');
		}
		
		$this->view->fp = $fp;
	}
	
	public function previewAction(){
		
		$id_post = (int) $this->getRequest()->getQuery('id');
		
		$post = new DatabaseObject_BlogPost($this->db);

		if (!$post->loadForUser($this->identity->id_utente, $id_post)) {		
			$this->_redirect($this->getUrl());
		}
				
		$this->breadcrumbs->addStep('Anteprima post: ' . $post->profile->title);
		$this->view->post = $post;
		
		return ;
		
	}
	
	public function setstatusAction(){
		$request = $this->getRequest();
		$id_post = (int) $request->getPost('id');
		
		$post = new DatabaseObject_BlogPost($this->db);
		if (!$post->loadForUser($this->identity->id_utente, $id_post)) {
			$this->_redirect($this->getUrl());
		}
				
		// URL di reindirizzamento
		$url = $this->getUrl('preview') . '?id=' .$post->getId();
		
		if ($request->getPost('edit')) {
			$this->_redirect($this->getUrl('edit') . '?id=' . $post->getId());
		} else if ($request->getPost('publish')) {
			$post->sendLive();
			$post->save();
			$this->messenger->addMessage('Post inviato live');
		} else if ($request->getPost('unpublish')) {
			$post->sendBackToDraft();
			$post->save();
			$this->messenger->addMessage('Anteprima Post');
		} else if ($request->getPost('delete')) {
			$post->delete();
			
			// torna all'indice perch� l'anteprima di questa pagina non esiste pi�
			$url = $this->getUrl();
			$this->messenger->addMessage('Post cancellato');
		}
		
		$this->_redirect($url);
		
		return ;
	}
	
	public function tagsAction()
	{
		$request = $this->getRequest();
		
		$post_id = (int) $request->getPost('id');
		
		$post = new DatabaseObject_BlogPost($this->db);
		if (!$post->loadForUser($this->identity->id_utente, $post_id))
			$this->_redirect($this->getUrl());
			
		$tag = $request->getPost('tag');
		if ($request->getPost('add')) {
			$post->addTags($tag);
			$this->messenger->addMessage('Tag aggiunto al post');
		} else if ($request->getPost('delete')) {
			$post->deleteTags($tag);
			$this->messenger->addMessage('Tag eliminato dal post');
		}
		$url = $this->getUrl('preview') . '?id=' . $post->getId();
		$this->_redirect($url);
	}
	
	public function imagesAction()
	{
		$request = $this->getRequest();
		$json = array();
		
		$post_id = (int)$request->getPost('id');
		$post = new DatabaseObject_BlogPost($this->db);
		
		if (!$post->loadForUser($this->identity->id_utente, $post_id))
			$this->_redirect($this->getUrl());
		
		if ($request->getPost('upload')) {
			$fp = new FormProcessor_BlogPostImage($post);
			if ($fp->process($request)) {
				$this->messenger->addMessage('Immagine caricata');
			} else {
				foreach ($fp->getErrors() as $error) {
					$this->messenger->addMessage($error);
				}
			}
		} else if ($request->getPost('reorder')) {
			$order = $request->getPost('post_images');
			$post->setImageOrder($order);
		} else if ($request->getPost('delete')) {
			$id_immagine = (int)$request->getPost('image');
			$image = new DatabaseObject_BlogPostImage($this->db);
			if ($image->loadForPost($post->getId(), $id_immagine)) {
				$image->delete();
				if ($request->isXmlHttpRequest()) {
					$json = array('deleted' => true, 'id_immagine' => $id_immagine);
				} else 
				$this->messenger->addMessage('Immagine cancellata');
			}
		}
		if ($request->isXmlHttpRequest()) {
			$this->sendJson($json);
		} else {
			$url = $this->getUrl('preview') . '?id=' . $post->getId();
			$this->_redirect($url);
		}
	}
	
	public function locationsAction()
	{
		$request = $this->getRequest();
		
		$id_post = (int) $request->getParam('id');
		$post = new DatabaseObject_BlogPost($this->db);
		
		if (!$post->loadForUser($this->identity->id_utente, $id_post)) {
			$this->_redirect($this->getUrl());
		}
		
		$this->breadcrumbs->addStep('Gestisci i luoghi');
		$this->view->post = $post;
	}
	
	public function locationsManageAction()
	{
		$request = $this->getRequest();
		
		$action = $request->getPost('action');
		$id_post = $request->getPost('id_post');
		
		$ret = array('id_post' => 0);
		
		$post = new DatabaseObject_BlogPost($this->db);
		
		if ($post->loadForUser($this->identity->id_utente, $id_post)) {
			$ret['id_post'] = $post->getId();
		
			switch ($action) {
				case 'get':
					$ret['locations'] = array();
					foreach($post->luoghi as $location) {
						$ret['locations'][] = array(
							'id_luogo' 		=> $location->getId(),
							'latitudine'	=> $location->latitudine,
							'longitudine'	=> $location->longitudine,
							'descrizione'	=> $location->descrizione
						);
					}
					break;
				
				case 'add':
					$fp = new FormProcessor_BlogPostLocation($post);
					if ($fp->process($request)) {
						
						$ret['id_luogo']    = $fp->location->getId();
						$ret['latitudine']  = $fp->location->latitudine;
						$ret['longitudine'] = $fp->location->longitudine;
						$ret['descrizione'] = $fp->location->descrizione;
					} else {
						$ret['id_luogo'] = 0;
					}
					break;
				
				case 'delete':
					$id_luogo = $request->getPost('id_luogo');
					$location = new DatabaseObject_BlogPostLocation($this->db);
					if ($location->loadForPost($post->getId(), $id_luogo)) {
						$ret['id_luogo'] = $location->getId();
						$location->delete();
					}
					break;	
				
				case 'move':
					$id_luogo = $request->getPost('id_luogo');
					$location = new DatabaseObject_BlogPostLocation($this->db);
					if ($location->loadForPost($post->getId(), $id_luogo)) {
						$location->longitudine = $request->getPost('longitudine');
						$location->latitudine = $request->getPost('latitudine');
						$location->save();
						
						$ret['id_luogo']    = $location->getId();
						$ret['latitudine']  = $location->latitudine;
						$ret['longitudine'] = $location->longitudine;
						$ret['descrizione'] = $location->descrizione;					
					}
					break;	
			}
		}
		
		$this->sendJson($ret);
	}
}