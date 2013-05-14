<?php
/**
 * 
 * Questa classe gestisce i breadcrumbs per tutto il sito
 * bisogna inserire per ogni azione il titolo e se � necessario l'url
 * precedente
 * 
 * @author filippo
 *
 */
    class Breadcrumbs
    {
        private $_trail = array();

        public function addStep($title, $link = '')
        {
            $this->_trail[] = array('title' => $title,
                                    'link'  => $link);
        }

        public function getTrail()
        {
            return $this->_trail;
        }

        public function getTitle()
        {
            if (count($this->_trail) == 0)
                return null;

            return $this->_trail[count($this->_trail) - 1]['title'];
        }
    }
?>