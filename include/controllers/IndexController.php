<?php
    class IndexController extends CustomControllerAction
    {
        public function indexAction()
        {
        	
			// inizializza le opzioni
			$options = array(
				'status'		=> DatabaseObject_BlogPost::STATUS_LIVE,
				'limit'			=> 2,
				'order'			=> 'p.ts_creazione desc',
				'public_only'	=> true	
			);

			//recupera i post dal blog
			$posts = DatabaseObject_BlogPost::GetPosts($this->db, $options);
			
			//prendo gli utenti dei post
			$user_ids = array();
			foreach ($posts as $post)
				$user_ids[$post->id_utente] = $post->id_utente;
			
			//carica i record utente
			if (count($user_ids) > 0) {
				$options = array('id_utente' => $user_ids);
				$users = DatabaseObject_User::GetUsers($this->db, $options);
			} else {
				$users = array();
			}	
			
			//assegna post e utenti al modello
			$this->view->posts = $posts;
			$this->view->users = $users;
			
        }
    }
?>