<?php
    class CustomControllerAction extends Zend_Controller_Action
    {
        public $db;
		public $breadcrumbs;
		public $messenger;
		
        function init()
        {
            $this->db = Zend_Registry::get('db');
            
            $this->view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8');
			$this->view->headLink()->appendStylesheet('/css/styles.css');
		 	
			$this->view->headScript()->appendFile('/js/prototype.js', 'text/javascript')
            						 ->appendFile('/js/scriptaculous/scriptaculous.js', 'text/javascript')
            						 ->appendFile('/js/SearchSuggestor.class.js', 'text/javascript')
            						 ->appendFile('/js/scripts.js', 'text/javascript');
            
			$this->breadcrumbs = new Breadcrumbs();
            $this->breadcrumbs->addStep('Home', $this->getUrl(null, 'index'));            
        	
            $this->messenger = $this->_helper->_flashMessenger;
        }

        public function preDispatch()
        {
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $this->view->authenticated = true;
                $this->view->identity = $auth->getIdentity();
            }
            else
                $this->view->authenticated = false;
        }
    
        public function postDispatch()
        {
        	$request = $this->getRequest();
            $this->view->trail = $this->breadcrumbs->getTrail();
            $this->view->titolo = $this->breadcrumbs->getTitle();
            $this->view->headTitle($this->breadcrumbs->getTitle());
            
            $this->view->messages = $this->messenger->getMessages();
        	$this->view->isXmlHttpRequest = $this->getRequest()->isXmlHttpRequest();
        	
        	$this->view->config = Zend_Registry::get('config'); 
        	
        }

        
        public function getUrl($action = null, $controller = null)
        {
            $url = $this->_helper->url->simple($action, $controller);
            return $url;
        }
        
        public function getCustomUrl($options, $route = null)
    	{
        	return $this->_helper->url->url($options, $route);
        }
        
        public function sendJson($data)
        {
            $this->_helper->viewRenderer->setNoRender();

            $this->getResponse()->setHeader('content-type', 'application/json');
            echo Zend_Json::encode($data);
        }
                        
    }
?>