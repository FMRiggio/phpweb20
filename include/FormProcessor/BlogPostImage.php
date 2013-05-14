<?php
class FormProcessor_BlogPostImage extends FormProcessor
{
	protected $post = null;
	public 	  $image;
         
	public function __construct(DatabaseObject_BlogPost $post)
	{
		parent::__construct();

		$this->post = $post;
		
		//imposta i valori iniziali per la nuova immagine
		$this->image = new DatabaseObject_BlogPostImage($post->getDb());
		$this->image->id_post = $this->post->getId();
	}
	
	public function process(Zend_Controller_Request_Abstract $request)
	{
		if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
			$this->addError('image', 'Caricamento dati non valido');
			return false;
		}
		
		$file = $_FILES['image'];
		
		switch($file['error']) {
			case UPLOAD_ERR_OK:
				//riuscito
				break;
			case UPLOAD_ERR_FORM_SIZE:
				//usato solo se nel form è specificato MAX_FILE_SIZE
			case UPLOAD_ERR_INI_SIZE:
				$this->addError('image', 'Il file caricato è troppo grande');
				break;
			case UPLOAD_ERR_PARTIAL:
				$this->addError('image', 'Il file è stato caricato solo parzialmente');
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$this->addError('image', 'Cartella temporanea non trovata');
				break;				
			case UPLOAD_ERR_CANT_WRITE:
				$this->addError('image', 'Scrittura file impossibile');
				break;
			case UPLOAD_ERR_EXTENSION:
				$this->addError('image', 'Estensione file non valida');
				break;
			default:
				$this->addError('image', 'Codice di errore sconosciuto');
		}
		
		if ($this->hasError())
			return false;
		
		$info = getimagesize($file['tmp_name']);
		if (!$info) {
			$this->addError('type', 'Il file caricato non è un immagine');
			return false;
		}
		
		switch ($info[2]) {
			case IMAGETYPE_PNG:
			case IMAGETYPE_GIF:
			case IMAGETYPE_JPEG:
				break;
			default:
				$this->addError('type', 'Tipo immagine caricata non valido');
				return false;
		}
		
		//se non ci sono errori salvo l'immagine
		if (!$this->hasError()) {
			$this->image->uploadFile($file['tmp_name']);
			$this->image->nome_file = basename($file['name']);
			$this->image->save();
		}
		return !$this->hasError();
	}


}
?>