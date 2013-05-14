<?php
class NewsController extends Zend_Controller_Action
{
	protected $_flashMessenger = null;
	
	public function init()
	{
		$this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
		
		if($this->_hasParam('annulla')) {
			$this->_redirect('/');
		}
	}
	
	public function viewAction()
	{
		$news = new News();

		$newsId = $this->_getParam('id');
		$news = $news->getNewsById($newsId);
		
		if(!$news) {
			throw new Zend_Controller_Action_Exception;
		}

		$this->view->titoloPagina = $news['titolo'];
		$this->view->news = $news;
	}
	
	public function deleteAction()
	{
		$news = new News();
		$newsId = $this->_getParam('id');
		$news->deleteNews($newsId);
		$this->_flashMessenger->addMessage("Cancellazione della news avvenuta con successo");
		$this->_redirect('/');
	}

	public function newAction()
	{
		$this->view->titoloPagina = 'Inserisci una news';

		// Utilizza 'news/news-form.phtml' invece di  'news/new.phtml'
		$this->_helper->viewRenderer->setScriptAction('news-form');

		$request = $this->getRequest();
		if($request->isPost())
		{
			$news = new News;

			$autore        = $request->getPost('autore');
			$titolo        = $request->getPost('titolo');
			$testoIntro    = $request->getPost('testo_intro');
			$testoCompleto = $request->getPost('testo_completo');

			try {
				$news->insertUpdateNews(false,$autore,$titolo,$testoIntro,$testoCompleto);
				$this->_flashMessenger->addMessage("News '$titolo' memorizzata con successo");
				$this->_redirect('/');
			} catch (Zend_Exception $e) {
				$this->view->errorMsg = $e->getMessage();
				$this->view->news = array('autore' => $autore,
				'titolo'        => $titolo,
				'testo_intro'   => $testoIntro,
				'testoCompleto' => $testoCompleto);
			}
		}
	}

	public function editAction()
	{
		$this->view->titoloPagina = 'Modifica news';

		// Utilizza il template 'news/news-form.phtml' al posto di  'news/edit.phtml'
		$this->_helper->viewRenderer->setScriptAction('news-form');

		$news = new News;

		$request = $this->getRequest();
		$newsId  = $request->getParam('id');
		if($request->isPost())
		{
			$autore        = $request->getPost('autore');
			$titolo        = $request->getPost('titolo');
			$testoIntro    = $request->getPost('testo_intro');
			$testoCompleto = $request->getPost('testo_completo');
				
			try {
				$news->insertUpdateNews($newsId,$autore,$titolo,$testoIntro,$testoCompleto);
				$this->_flashMessenger->addMessage("News '$titolo' modificata con successo");
				$this->_redirect('/');
			} catch (Zend_Exception $e) {
				$this->view->errorMsg = $e->getMessage();
				$this->view->news = array($autore,$titolo,$testoIntro,$testoCompleto);
				$this->view->news = array('autore' => $autore,
				'titolo'        => $titolo,
				'testo_intro'   => $testoIntro,
				'testoCompleto' => $testoCompleto);
			}
		}
		else
		{
			$news = $news->getNewsById($newsId);
			if(!$news) {
				throw new Zend_Controller_Action_Exception;
			}
			$this->view->news = $news;
		}
	}
}
?>