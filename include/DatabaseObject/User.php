<?php
    class DatabaseObject_User extends DatabaseObject
    {
        static $userTypes = array('member'        => 'Member',
                                  'administrator' => 'Administrator');

        public $profile = null;
        public $_newPassword = null;

        public function __construct($db)
        {
            parent::__construct($db, 'utenti', 'id_utente');

            $this->add('username');
            $this->add('password');
            $this->add('tipo_utente', 'member');
            $this->add('ts_creazione', time(), self::TYPE_TIMESTAMP);
            $this->add('ts_ultimo_login', null, self::TYPE_TIMESTAMP);

            $this->profile = new Profile_User($db);
        }

        protected function preInsert()
        {
            $this->_newPassword = Text_Password::create(8);
            $this->password = $this->_newPassword;
            
            // impostazioni predefinite dell'account
            $this->profile->blog_public = false;
            $this->profile->num_posts = 10;
            
            return true;
        }

        protected function postLoad()
        {
            $this->profile->setUserId($this->getId());
            $this->profile->load();
        }

        protected function postInsert()
        {
            $this->profile->setUserId($this->getId());
            $this->profile->save(false);
			$map = array('nome' 		=> $this->profile->nome,
						 'username' 	=> $this->username,
						 'newPassword' 	=> $this->_newPassword);
            $this->sendEmail('user-register.phtml', $map);
            return true;
        }

        protected function postUpdate()
        {
            $this->profile->save(false);
            return true;
        }

        protected function preDelete()
        {
            $this->profile->delete();
            return true;
        }

        public function sendEmail($tpl, $map)
        {
			
            // fetch the e-mail body
            $body = $this->loadFile($tpl, $map);
    	            
            // extract the subject from the first line
            list($subject, $body) = preg_split('/\r|\n/', $body, 2);

            // now set up and send the e-mail
            $mail = new Zend_Mail();

            // set the to address and the user's full name in the 'to' line
            $mail->addTo($this->profile->email,
                         trim($this->profile->nome . ' ' .
                              $this->profile->cognome));

            // get the admin 'from' details from the config
            $mail->setFrom(Zend_Registry::get('config')->email->from->email,
            Zend_Registry::get('config')->email->from->name);

            // set the subject and body and send the mail
            $mail->setSubject(trim($subject));
            $mail->setBodyText(trim($body));
            //$mail->send();
        }

        public function createAuthIdentity()
        {
            $identity = new stdClass;
            $identity->id_utente = $this->getId();
            $identity->username = $this->username;
            $identity->tipo_utente = $this->tipo_utente;
            $identity->nome = $this->profile->nome;
            $identity->cognome = $this->profile->cognome;
            $identity->email = $this->profile->email;

            return $identity;
        }

        public function loginSuccess()
        {
            $this->ts_last_login = time();
            unset($this->profile->new_password);
            unset($this->profile->new_password_ts);
            unset($this->profile->new_password_key);
            $this->save();

            $message = sprintf('Successful login attempt from %s user %s',
                               $_SERVER['REMOTE_ADDR'],
                               $this->username);

            $logger = Zend_Registry::get('logger');
            $logger->notice($message);
        }
        static public function LoginFailure($username, $code = '')
        {
            switch ($code) {
                case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                    $reason = 'Unknown username';
                    break;
                case Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS:
                    $reason = 'Multiple users found with this username';
                    break;
                case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
                    $reason = 'Invalid password';
                    break;
                default:
                    $reason = '';
            }

            $message = sprintf('Failed login attempt from %s user %s',
                               $_SERVER['REMOTE_ADDR'],
                               $username);

            if (strlen($reason) > 0)
                $message .= sprintf(' (%s)', $reason);

            $logger = Zend_Registry::get('logger');
            $logger->warn($message);
        }

        public function fetchPassword()
        {
            if (!$this->isSaved())
                return false;

            // generate new password properties
            $this->_newPassword = Text_Password::create(8);
            $this->profile->new_password = md5($this->_newPassword);
            $this->profile->new_password_ts = time();
            $this->profile->new_password_key = md5(uniqid() .
                                                   $this->getId() .
                                                   $this->_newPassword);

            // save new password to profile and send e-mail
            $this->profile->save();
            $map = array('idUtente'       => $this->id_utente,
            			 'nome' 		  => $this->profile->nome,
						 'username' 	  => $this->username,
						 'newPassword' 	  => $this->_newPassword,
            			 'newPasswordKey' => $this->profile->new_password_key);
            $this->sendEmail('user-recupero-password.phtml', $map);

            return true;
        }

        public function confirmNewPassword($key)
        {
            // check that valid password reset data is set
            if (!isset($this->profile->new_password)
                || !isset($this->profile->new_password_ts)
                || !isset($this->profile->new_password_key)) {

                return false;
            }

            // check if the password is being confirm within a day
            if (time() - $this->profile->new_password_ts > 86400)
                return false;

            // check that the key is correct
            if ($this->profile->new_password_key != $key)
                return false;

            // everything is valid, now update the account to use the new password

            // bypass the local setter as new_password is already an md5
            parent::__set('password', $this->profile->new_password);

            unset($this->profile->new_password);
            unset($this->profile->new_password_ts);
            unset($this->profile->new_password_key);

            // finally, save the updated user record and the updated profile
            return $this->save();
        }

        public function usernameExists($username)
        {
            $query = sprintf('select count(*) as num from %s where username = ?',
                             $this->_table);
			
            $result = $this->_db->fetchOne($query, $username);
			
            return $result > 0;
        }

        static public function IsValidUsername($username)
        {
            $validator = new Zend_Validate_Alnum();
            return $validator->isValid($username);
        }

        public function __set($name, $value)
        {
            switch ($name) {
                case 'password':
                    $value = md5($value);
                    break;

                case 'user_type':
                    if (!array_key_exists($value, self::$userTypes))
                        $value = 'member';
                    break;
            }

            return parent::__set($name, $value);
        }
        
	    /** carica il template della mail e sostituisce i placeholder 
		 * con i dati passati come parametro  
		 * 
		 * @param unknown_type $file
		 * @param array $map
		 * 
		 * @return string $html 
		 */	
		public function loadFile($file, array $map = array())
		{	
			$view = new Zend_View();
			$view->addScriptPath(Zend_Registry::get('config')->paths->email->templates);
			
			$html = $view->render($file);
	
	    	foreach($map as $key => $value) {
	    		$html = preg_replace("/#$key#/", $value, $html);
	    	}
	    	return $html;
		}        
		
		
		public function loadByUsername($username)
		{
			$username = trim($username);
			
			if (strlen($username) == 0)
				return false;
			
			$query = sprintf('select %s from %s where username = ?', 
							 join(', ', $this->getSelectFields()),
							 $this->_table);
							 
			$query = $this->_db->quoteInto($query, $username);
			
			return $this->_load($query);
		}
		
		public static function GetUsers($db, $options = array())
		{
			// inizializza le opzioni
			$defaults = array(
				'offset'	=> 0,
				'limit'		=> 0,
				'order'		=> 'u.username'	
			);
			
			foreach ($defaults as $k => $v) {
				$options[$k] = array_key_exists($k, $options) ? $options[$k] : $v;
			}
			
			$select = self::_GetBaseQuery($db, $options);
			
			// prendo i campi da selezionare
			$select->from(null, 'u.*');
			
			//imposta offset, limite e ordinamento dei risultati
			if ($options['limit'] > 0)
				$select->limit($options['limit'], $options['offset']);
			
			$select->order($options['order']);
			
			//prendo gli utenti dal db
			$data = $db->fetchAll($select);
			
			// trasformo i dati in un array di DatabaseObject_User
			$users = parent::BuildMultiple($db, __CLASS__, $data);
			
			if (count($users) == 0)
				return $users;
			
			$user_ids = array_keys($users);
			
			// carico i dati di profilo per i post caricati
			$profiles = Profile::BuildMultiple($db, 'Profile_User', array('id_utente' => $user_ids));
			
			foreach ($users as $user_id => $user) {
				if (array_key_exists($user_id, $profiles) && $profiles[$user_id] instanceof Profile_User) {
					$users[$user_id]->profile = $profiles[$user_id];
				} else {
					$users[$user_id]->profile->setUserId($user_id);
				}
			}
			
			return $users;
		}
		
		
		public static function GetUsersCount($db, $options)
		{
			$select = self::_GetBaseQuery($db, $options);
			$select->from(null, 'count(*)');
			
			return $db->fetchOne($select);
		}
		
		private static function _GetBaseQuery($db, $options)
		{
			// inizializzo opzioni
			$defaults = array('id_utente' => array());
			
			foreach ($defaults as $k => $v) {
				$options[$k] = array_key_exists($k, $options) ? $options[$k] : $v;
			}	

			// creo una query di selezione della tabella utenti
			$select = $db->select();
			$select->from(array('u' => 'utenti'), array());
			
			//filtro i risultati in base agli Id passati
			if (count($options['id_utente']) > 0) {
				$select->where('u.id_utente in (?)', $options['id_utente']);
			}

			return $select;
		}
		
    }
?>