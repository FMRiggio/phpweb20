<?php
    class FormProcessor_UserRegistration extends FormProcessor
    {
        protected $db = null;
        public $user = null;
		protected $_validateOnly = false;
		
        public function __construct($db)
        {
            parent::__construct();
            $this->db = $db;
            $this->user = new DatabaseObject_User($db);
            $this->user->tipo_utente = 'member';
        }
		
       	
        public function validateOnly($flag) {
       		$this->_validateOnly = (bool)$flag;	
       	}
       	
        public function process(Zend_Controller_Request_Abstract $request)
        {
        	
            // validate the username
            $this->username = trim($request->getPost('username'));

            if (strlen($this->username) == 0) {
                $this->addError('username', 'Please enter a username');
            } else if (!DatabaseObject_User::IsValidUsername($this->username)) {
                $this->addError('username', 'Please enter a valid username');
            } else if ($this->user->usernameExists($this->username)) {
                $this->addError('username', 'The selected username already exists');
            } else
                $this->user->username = $this->username;
			
            // validate the user's name
            $this->nome = $this->sanitize($request->getPost('nome'));
            if (strlen($this->nome) == 0)
                $this->addError('nome', 'Please enter your first name');
            else
                $this->user->profile->nome = $this->nome;
			
            $this->cognome = $this->sanitize($request->getPost('cognome'));
            if (strlen($this->cognome) == 0)
                $this->addError('last_name', 'Please enter your last name');
            else
                $this->user->profile->cognome = $this->cognome;
			
            // validate the e-mail address
            $this->email = $this->sanitize($request->getPost('email'));
            $validator = new Zend_Validate_EmailAddress();
		
            if (strlen($this->email) == 0)
                $this->addError('email', 'Please enter your e-mail address');
            else if (!$validator->isValid($this->email))
                $this->addError('email', 'Please enter a valid e-mail address');
            else
                $this->user->profile->email = $this->email;
			
            // validate CAPTCHA phrase
            $session = new Zend_Session_Namespace('captcha');
            $this->captcha = $this->sanitize($request->getPost('captcha'));

            if ($this->captcha != $session->phrase)
                $this->addError('captcha', 'Please enter the correct phrase');

            // if no errors have occurred, save the user
            if (!$this->_validateOnly && !$this->hasError()) {
                $this->user->save();
                unset($session->phrase);
            }

            // return true if no errors have occurred
            return !$this->hasError();
        }
    }
?>