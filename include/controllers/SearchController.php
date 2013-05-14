<?php
class SearchController extends CustomControllerAction
{
	public function indexAction()
	{
		$request = $this->getRequest();
		$q = trim($request->getParam('q'));
		
		$config = Zend_Registry::get('config');
		
		$search = array(
			'performed'	=>	false,
			'limit'		=>	$config->search->numberResult,
			'total'		=> 0,
			'start'		=> 0,
			'finish'	=> 0,
			'page'		=> (int) $request->getQuery('p'),
			'pages'		=> 1,
			'results'	=> array()
		);
		
		try {
			if (strlen($q) == 0)
				throw new Exception('Nessun termine di ricerca specificato');
				
			$path  = DatabaseObject_BlogPost::getIndexFullPath();
			$index = Zend_Search_Lucene::open($path);
			$hits  = $index->find($q);
			
			$search['performed'] = true;
			$search['total']	 = count($hits);
			$search['pages']	 = ceil($search['total'] / $search['limit']);
			$search['page']		 = max(1, min($search['pages'], $search['page']));
			
			$offset = ($search['page'] - 1) * $search['limit'];
			
			$search['start']  = $offset + 1;
			$search['finish'] = min($search['total'], $search['start'] + $search['limit'] - 1);
			
			$hits = array_slice($hits, $offset, $search['limit']);
			$post_ids = array();
			foreach ($hits as $hit) {
				$post_ids[] = (int) $hit->id_post;
			}
			
			$options = array('status'	=>	DatabaseObject_BlogPost::STATUS_LIVE,
							 'id_post'	=> $post_ids);
			$posts = DatabaseObject_BlogPost::GetPosts($this->db, $options);
			
			foreach ($post_ids as $id_post) {
				if (array_key_exists($id_post, $posts)) {
					$search['results'][$id_post] = $posts[$id_post];
				}
			}
			
			// determina quali post degli utenti sono stati recuperati
			$user_ids = array();
			foreach ($posts as $post) {
				$user_ids[$post->id_utente] = $post->id_utente;
			}
			//carica i record dell'utente
			if (count($user_ids) > 0) {
				$options = array('id_utente' => $user_ids);
				$users = DatabaseObject_User::GetUsers($this->db, $options);
			} else {
				$users = array();
			}
			
		}
		catch (Exception $ex) {
			//non viene eseguito niente
		}
		
		if ($search['performed']) {
			$this->breadcrumbs->addStep('Risultati ricerca per ' . $q);
		} else {
			$this->breadcrumbs->addStep('Cerca');
		}
		$this->view->q = $q;
		$this->view->search = $search;
		$this->view->users = $users;
		
	}
	
	
	public function suggestionAction()
	{
		$q = trim($this->getRequest()->getParam('q'));
		
		$suggestion = DatabaseObject_BlogPost::GetTagSuggestions($this->db, $q, 10);
		
		$this->sendJson($suggestion);
	}
}
?>