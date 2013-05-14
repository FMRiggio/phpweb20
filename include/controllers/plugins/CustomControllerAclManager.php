<?php
/*
 * CustomControllerAclManager - Created on 08/ott/10
 *
 * Author : Riggio Filippo Matteo - Intesys Web Developer Junior
 * 
 * Il costruttore definisce i ruoli, le risorse e le autorizzazioni 
 * 
 * Il metodo preDispatch cattura la richiesta dell'utentee la controlla 
 *
 *   
 */
 
 
 class CustomControllerAclManager extends Zend_Controller_Plugin_Abstract
 {
 
 	//ruolo utente definito in assenza di login o per un ruolo non valido
 	private $_defaultRole = 'guest';
 	
 	//azione da eseguire se un utente non ha privilegi sufficienti
 	private $_authController = array( 'controller' => 'account'
 									 ,'action'     => 'login'); 
	public function __construct(Zend_Auth $auth){
	
		$this->auth = $auth;
		$this->acl = new Zend_Acl();
		
		//aggiungo i ruoli utente
		$this->acl->addRole(new Zend_Auth_Role($this->_defaultRole));
		$this->acl->addRole(new Zend_Auth_Role('member'));
		$this->acl->addRole(new Zend_Auth_Role('administrator'), 'member');
		
		//aggiungo le risorse da controllare
		$this->acl->add(new Zend_Acl_Resource('account'));
		$this->acl->add(new Zend_Acl_Resource('admin'));
		
		//come impostazione predefinita tutti gli utenti sono guest ma non admin
		$this->acl->allow();
		$this->acl->deny(null, 'account');
		$this->acl->deny(null, 'admin');
		
		//aggiunge la possibilità agli ospiti di poter accedere/registrare
		$this->acl->allow('guest', 'account', array('login', 'fetchpassword', 'register', 'registerComplete'));
		
		//consente ai membri l'accesso alla sezione account
		$this->acl->allow('member', 'account');
		
		//consente agli admin l'accesso alla risorsa administration
		$this->acl->allow('administrator', 'admin');
		
	}
	
	/**
	 * preDispatch
	 * 
	 * Prima di ogni azione viene verificato se l'utente possiede 
	 * le autorizzazioni necessarie
	 *
	 * @param Zend_Controller_Request_Abstract $request 
	 */
	 
	 public function preDispatch(Zend_Controller_Request_Abstract $request){
	 
	 	// controllo se l'utente ha eseguito l'accesso e ha il suo ruolo prestabilito
	 	// in caso contrario gli assegno il ruolo di base (member)
	 	if ($this->auth->hasIdentity()){
	 		$role = $this->auth->getIdentity()->tipo_utente;
	 	} else {
	 		$role = $this->_defaultRole;
	 	}
	 	
	 	// la risorsa ACL è il nome del controller richiesto
	 	$resource = $request->controller;
	 	
	 	// il privilegio ACL è il nome dell'azione richiesta
	 	$privilegio = $request->action;
	 	
	 	// se la risorsa non è stata aggiunta in modo esplicito, verifica
	 	// le autorizzazioni globali predefinite
	 	if ((!$this->acl->isAllowed($role, $resource, $privilegio)){
	 		$request->setControllerName($this->_authController['controller']);
	 		$request->seActionName($this->_authController['action']);
	 	}
	 	
	 	
	 }
	 
	 
	 
}
?>
