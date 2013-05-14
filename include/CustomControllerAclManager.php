<?php
    class CustomControllerAclManager extends Zend_Controller_Plugin_Abstract
    {
        // default user role if not logged or (or invalid role found)
        private $_defaultRole = 'guest';

        // the action to dispatch if a user doesn't have sufficient privileges
        private $_authController = array('controller' => 'account',
                                         'action' => 'login');

        public function __construct(Zend_Auth $auth)
        {
            $this->auth = $auth;
            $this->acl = new Zend_Acl();

            // add the different user roles
            $this->acl->addRole(new Zend_Acl_Role($this->_defaultRole));
            $this->acl->addRole(new Zend_Acl_Role('member'));
            $this->acl->addRole(new Zend_Acl_Role('administrator'), 'member');

            // add the resources we want to have control over
            $this->acl->add(new Zend_Acl_Resource('account'));
            $this->acl->add(new Zend_Acl_Resource('admin'));
            $this->acl->add(new Zend_Acl_Resource('blogmanager'));

            // allow access to everything for all users by default
            // except for the account management and administration areas
            $this->acl->allow();
            $this->acl->deny(null, 'account');
            $this->acl->deny(null, 'blogmanager');
            $this->acl->deny(null, 'admin');
			
            // add an exception so guests can log in or register
            // in order to gain privilege
            $this->acl->allow('guest', 'account', array('login',
                                                        'recupero-password',
                                                        'register',
                                                        'registercomplete'));

            // allow members access to the account management area
            $this->acl->allow('member', 'account');
			$this->acl->allow('member', 'blogmanager');
			
            // allows administrators access to the admin area
            $this->acl->allow('administrator', 'admin');
        }

        /**
         * preDispatch
         *
         * Before an action is dispatched, check if the current user
         * has sufficient privileges. If not, dispatch the default
         * action instead
         *
         * @param Zend_Controller_Request_Abstract $request
         */
        public function preDispatch(Zend_Controller_Request_Abstract $request)
        {
            // check if a user is logged in and has a valid role,
            // otherwise, assign them the default role (guest)
            if ($this->auth->hasIdentity())
                $role = $this->auth->getIdentity()->tipo_utente;
            else
                $role = $this->_defaultRole;

            if (!$this->acl->hasRole($role))
                $role = $this->_defaultRole;

            // the ACL resource is the requested controller name
            $resource = $request->controller;

            // the ACL privilege is the requested action name
            $privilege = $request->action;

            // if we haven't explicitly added the resource, check
            // the default global permissions
            if (!$this->acl->has($resource))
                $resource = null;

            // access denied - reroute the request to the default action handler
            if (!$this->acl->isAllowed($role, $resource, $privilege)) {
                $request->setControllerName($this->_authController['controller']);
                $request->setActionName($this->_authController['action']);
            }
        }
    }
?>