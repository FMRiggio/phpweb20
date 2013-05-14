<?php
    class Profile_User extends Profile
    {
        public function __construct($db, $id_utente = null)
        {
            parent::__construct($db, 'profilo_utenti');

            if ($id_utente > 0)
                $this->setUserId($id_utente);
        }

        public function setUserId($id_utente)
        {
            $filters = array('id_utente' => (int) $id_utente);
            $this->_filters = $filters;
        }
    }
?>