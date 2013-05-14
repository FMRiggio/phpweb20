<?php
class DatabaseObject_BlogPostImage extends DatabaseObject
{
    protected $_uploadedFile;
	public function __construct($db)
	{
		parent::__construct($db, 'blog_post_immagini', 'id_immagine');
		
		$this->add('nome_file');
		$this->add('id_post');
		$this->add('ranking');
		
	}
	
	public static function getUploadPath()
	{
		$config = Zend_Registry::get('config');
		return sprintf('%s/uploaded-files', $config->paths->data);
	}
	
	public function getFullPath()
	{
		return sprintf('%s/%d', self::getUploadPath(), $this->getId());
	}
		
	public function uploadFile($path)
	{
		if (!file_exists($path) || !is_file($path))
			throw new Exception('Impossibile trovare il file esistente');
			
		if (!is_readable($path))
			throw new Exception('Impossibile leggere il file caricato', $code);
		
		$this->_uploadedFile = $path;
		
	}
	
	public function preInsert()
	{
		//verifica se la directory di caricamento è scrivibile
		$path = self::getUploadPath();
		if (!file_exists($path) || !is_dir($path))
			throw new Exception('Percordo di caricamento ' . $path . ' non trovato');
		if (!is_writable($path))
			throw new Exception('Impossibile scrivere sul percorso di caricamento ' . $path);
		
		//determino l'ordine della nuova immagine
		$query = sprintf(
			'select coalesce(max(ranking), 0) + 1 from %s where id_post = %d', 
			$this->_table, 
			$this->id_post
		);	
		$this->ranking = $this->_db->fetchOne($query);
		
		return true;
	}
	
	public function postInsert()
	{
		
		if (strlen($this->_uploadedFile) > 0)
			return move_uploaded_file($this->_uploadedFile, $this->getFullPath());
		
		return false;
	}	
	
	public static function GetThumbnailPath()
	{
		$config = Zend_Registry::get('config');
		return sprintf('%s/tmp/thumbnails', $config->paths->data);
	}
	
	public function createThumbnail($maxW, $maxH)
	{
		$fullpath = $this->getFullPath();
		
		$ts   = (int) filemtime($fullpath);
		$info = getImageSize($fullpath);
		
		$w	= $info[0]; //larghezza originale
		$h	= $info[1]; //altezza originale
		
		$ratio = $w / $h; //determino proporzione

		$maxW = min($w, $maxW); //la nuova larghezza non può superare $maxW
		if ($maxW == 0)
			$maxW = $w;

		$maxH = min($h, $maxH); //la nuova altezza non può superare $maxW
		if ($maxH == 0)
			$maxH = $h;

		$newW = $maxW;
		$newH = $newW / $ratio;
		
		// se l'altezza è troppo grande lascio quella massima
		// e determino la nuova larghezza
		if ($newH > $maxH) {
			$newH = $maxH;
			$newW = $newH * $ratio;
		}
		
		if ($w == $newW && $h == $newW) {
			//nessuna miniatura voluta, ritorno l'originale
			return $fullpath;
		}

		switch ($info[2]) {
			case IMAGETYPE_GIF:
				$infunc  = 'ImageCreateFromGif';
				$outfunc = 'ImageGif';
			break;
			case IMAGETYPE_JPEG:
				$infunc  = 'ImageCreateFromJpeg';
				$outfunc = 'ImageJpeg';
			break;		
			case IMAGETYPE_PNG:
				$infunc  = 'ImageCreateFromPng';
				$outfunc = 'ImagePng';
			break;						
			default:
				throw new Exception('Tipo immagine non valido');
			break;
		}
		
		// crea un nome file unico in base alle opzioni specificate
		$filename = sprintf('%d.%dx%d.%d',$this->getId(), $newW, $newH, $ts);
		
		//crea in automatico la directory per memorizzare le miniature
		$path = self::GetThumbnailPath();
		if (!file_exists($path))
			mkdir($path, 0777);
			
		if (!is_writable($path))
			throw new Exception('Impossibile scrivere nella directory thumbnails');
			
		// determina il percorso della nuova thumb
		$thumbPath = sprintf('%s/%s', $path, $filename);
		
		if (!file_exists($thumbPath)) {
			//legge l'img in GD
			$im = @$infunc($fullpath);
			if (!$im)
				throw new Exception('Impossibile leggere il file immagine');
		
			// creo la thumb
			$thumb = ImageCreateTrueColor($newW, $newH);
			ImageCopyResampled($thumb, $im, 0, 0, 0, 0, $newW, $newH, $w, $h);		
		
			$outfunc($thumb, $thumbPath);
			
		}
		
		if (!file_exists($thumbPath))
			throw new Exception('Si è verificato un errore sconosciuto');
		if (!is_readable($thumbPath))
			throw new Exception('Impossibile leggere la thumbnail');

		return $thumbPath;	
			
	}
	
	public static function GetImageHash($id, $w, $h)
	{
		$id = (int)$id;
		$w  = (int)$w;
		$h  = (int)$h;
		return md5(sprintf('%s,%s,%s', $id, $w, $h));
	}
	
	public static function GetImages($db, $options = array())
	{
		//inizializza le opzioni
		$defaults = array('id_post' => array());
		
		foreach ($defaults as $k => $v) {
			$options[$k] = array_key_exists($k, $options) ? $options[$k] : $v;
		}
		
		$select = $db->select();
		$select->from(array('i' => 'blog_post_immagini'), array('i.*'));
		
		//filtro in base all'id del post
		if (count($options['id_post']))
			$select->where('i.id_post in (?)', $options['id_post']);
			
		$select->order('i.ranking');
		
		//acquisisce i dati del post dal db
		$data = $db->fetchAll($select);
		
		//trasforma i dati in un array di oggetti BlogPostImage
		$images = parent::BuildMultiple($db, __CLASS__, $data);
		
		return $images;
	}
	
	
	public function preDelete()
	{
		unlink($this->getFullPath());
		$pattern = sprintf('%s/%d.*', self::GetThumbnailPath(), $this->getId());
		
		foreach (glob($pattern) as $thumbnail) {
			unlink($thumbnail);
		}
		return true;
	}
	
	public function loadForPost($id_post, $id_immagine)
	{
		$id_post 	 = (int)$id_post;
		$id_immagine = (int)$id_immagine;
		
		if ($id_post <= 0 || $id_immagine <= 0) {
			return false;
		}
		
		$query = sprintf(
			'select %s from %s where id_post = %d AND id_immagine = %d',
			join(', ', $this->getSelectFields()),
			$this->_table,
			$id_post,
			$id_immagine
		);
		
		return $this->_load($query);
	}
}
?>