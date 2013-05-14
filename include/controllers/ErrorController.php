<?php
class ErrorController extends Custom_Controller_Action
{

    public function errorAction()
    {
    	$request = $this->getRequest();
    	
        $errors = $request->getParam('error_handler');
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				$this->_forward('error404');
            	return;
            
        	case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
            default:
                // application error
                $this->getResponse()->clearBody();
                Zend_Registry::get('logger')->crit($error->exception->getMessage());
        }
        
        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->crit($this->view->message, $errors->exception);
        }
        
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
        
        $this->view->request   = $errors->request;
    }

    
    public function error404Action()
    {
    	$request = $this->getRequest();
    	
        $errors  = $request->getParam('error_handler');
        $uri     = $request->getRequestUri();
        
        Zend_Registry::get('logger')->info('404 error occurred: ' . $uri);
        $this->getResponse->setHttpResponseCode(404);
		$this->breadcrumbs->addStep('404 File not found');
		$this->view->requestedAddress = $uri;
		
    }

}

