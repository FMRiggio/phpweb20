<?php
    class FormProcessor_UserDetails extends FormProcessor
    {
        protected $db = null;
        public $user = null;
		
        public $publicProfile = array(
        	'public_first_name'	=> 'First Name',
        	'public_last_name'	=> 'Last Name',
        	'public_home_phone' => 'Home Phone',
        	'public_work_phone' => 'Work Phone',
        	'public_email'		=> 'Public Email'
        );
        
        public function __construct($db, $user_id)
        {
            parent::__construct();

            $this->db = $db;
            $this->user = new DatabaseObject_User($db);
            $this->user->load($user_id);

            $this->email 		= $this->user->profile->email;
            $this->nome 		= $this->user->profile->nome;
            $this->cognome 		= $this->user->profile->cognome;
            
            $this->blog_public  = $this->user->profile->blog_public;
            $this->num_posts 	= $this->user->profile->num_posts;
            
            foreach ($this->publicProfile as $key => $value) {
            	$this->key = $this->user->profile->$key;
            }
        }

        public function process(Zend_Controller_Request_Abstract $request)
        {
            // validate the user's name

            $this->nome = $this->sanitize($request->getPost('nome'));
            if (strlen($this->nome) == 0)
                $this->addError('nome', 'Please enter your first name');
            else
                $this->user->profile->nome = $this->nome;

            $this->cognome = $this->sanitize($request->getPost('cognome'));
            if (strlen($this->cognome) == 0)
                $this->addError('cognome', 'Please enter your last name');
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

            // check if a new password has been entered and if so validate it
            $this->password = $this->sanitize($request->getPost('password'));
            $this->password_confirm = $this->sanitize($request->getPost('password_confirm'));

            if (strlen($this->password) > 0 || strlen($this->password_confirm) > 0) {
                if (strlen($this->password) == 0)
                    $this->addError('password', 'Please enter the new password');
                else if (strlen($this->password_confirm) == 0)
                    $this->addError('password_confirm', 'Please confirm your new password');
                else if ($this->password != $this->password_confirm)
                    $this->addError('password_confirm', 'Please retype your password');
                else
                    $this->user->password = $this->password;
            }

            // elabora le impostazioni utente
            $this->blog_public = (bool) $request->getPost('blog_public');
            $this->num_posts = max(1,(int) $request->getPost('num_posts'));
            
            $this->user->profile->blog_public = $this->blog_public;
            $this->user->profile->num_posts = $this->num_posts;
            
            //prendo il profilo pubblico
            foreach ($this->publicProfile as $key => $value) {
            	$this->$key = $this->sanitize($request->getPost($key));
            	$this->user->profile->$key = $this->$key;	
            }
            
            // if no errors have occurred, save the user
            if (!$this->hasError()) {
                $this->user->save();
            }
            
            // return true if no errors have occurred
            return !$this->hasError();
        }
    }
?>