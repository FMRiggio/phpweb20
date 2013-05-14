<?php
class FormProcessor_BlogPostLocation extends FormProcessor
{
	protected $post;
	public $location;
         
	public function __construct(DatabaseObject_BlogPost $post)
	{
		parent::__construct();
		
		$this->post = $post;
		
		//imposta i valori iniziali per il nuovo luogo
		$this->location = new DatabaseObject_BlogPostLocation($post->getDb());
		$this->location->id_post = $this->post->getId();
		
	}

	public function process(Zend_Controller_Request_Abstract $request)
	{
		$this->descrizione = $this->sanitize($request->getPost('descrizione'));
		$this->longitudine = $request->getPost('longitudine');
		$this->latitudine  = $request->getPost('latitudine');
		// se non ci sono errori salvo
		if (!$this->hasError()) {
			$this->location->descrizione = $this->descrizione;
			$this->location->longitudine = $this->longitudine;
			$this->location->latitudine = $this->latitudine;
			$this->location->save();
		}
		return !$this->hasError();
	}
	
}
?>