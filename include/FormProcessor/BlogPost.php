<?php
    class FormProcessor_BlogPost extends FormProcessor
    {
        protected $db = null;
        public $user = null;
        public $post = null;
        static $tags = array(
            'a'      => array('href', 'target', 'name'),
            'img'    => array('src', 'alt'),
            'b'      => array(),
            'strong' => array(),
            'em'     => array(),
            'i'      => array(),
            'ul'     => array(),
            'li'     => array(),
            'ol'     => array(),
            'p'      => array(),
            'br'     => array()
         );
         
        public function __construct($db, $user_id, $post_id = 0)
        {
            parent::__construct();

            $this->db = $db;

            $this->user = new DatabaseObject_User($db);
            
            $this->user->load($user_id);
			
            $this->post = new DatabaseObject_BlogPost($db);
            $this->post->loadForUser($this->user->getId(), $post_id);

            if ($this->post->isSaved()) {
                $this->title = $this->post->profile->title;
                $this->content = $this->post->profile->content;
                $this->ts_creazione = $this->post->ts_creazione;
            }
            else
                $this->post->id_utente = $this->user->getId();
        }

        public function process(Zend_Controller_Request_Abstract $request)
        {
            $this->title = $this->sanitize($request->getPost('title'));
            $this->title = substr($this->title, 0, 255);

            if (strlen($this->title) == 0)
                $this->addError('title', 'Please enter a title for this post');
			
            $data = $request->getPost('ts_creazione');
            
            $date = array(
                          'y' => (int) substr($data,6, 4),
                          'm' => (int) substr($data,3, 2),
                          'd' => (int) substr($data,0, 2)
                         );

            $time = array(
                          'h' => (int) substr($data,11, 2),
                          'm' => (int) substr($data,14, 2)
                         );

            $time['h'] = max(1, min(12, $time['h']));
            $time['m'] = max(0, min(59, $time['m']));
			
            /*
            $meridian = strtolower($request->getPost('ts_createdMeridian'));
            if ($meridian != 'pm')
                $meridian = 'am';
			
            // convert the hour into 24 hour time
            if ($time['h'] < 12 && $meridian == 'pm')
                $time['h'] += 12;
            else if ($time['h'] == 12 && $meridian == 'am')
                $time['h'] = 0;
			*/

            if (!checkDate($date['m'], $date['d'], $date['y']))
                $this->addError('ts_creazione', 'Please select a valid date');

            $this->ts_creazione = $request->getPost('ts_creazione');

            $this->content = $this->cleanHtml($request->getPost('content'));

            // if no errors have occurred, save the blog post
            if (!$this->hasError()) {
            	
                $this->post->profile->title = $this->title;
                $this->post->ts_creazione =  mktime($time['h'],
	                                         $time['m'],
	                                         0,
	                                         $date['m'],
	                                         $date['d'],
	                                         $date['y']);
                $this->post->profile->content = $this->content;

                $preview = !is_null($request->getPost('preview'));
                if (!$preview)
                    $this->post->sendLive();

                $this->post->save();
                
            }

            // return true if no errors have occurred
            return !$this->hasError();
        }

        // temporary placeholder
        protected function cleanHtml($html)
        {
            $chain = new Zend_Filter();
            $chain->addFilter(new Zend_Filter_StripTags(self::$tags));
            $chain->addFilter(new Zend_Filter_StringTrim());

            $html = $chain->filter($html);

            $tmp = $html;
            while (1) {
                // Try and replace an occurrence of javascript:
                $html = preg_replace('/(<[^>]*)javascript:([^>]*>)/i',
                                     '$1$2',
                                     $html);

                // If nothing changed this iteration then break the loop
                if ($html == $tmp)
                    break;

                $tmp = $html;
            }

            return $html;
        }
    }
?>