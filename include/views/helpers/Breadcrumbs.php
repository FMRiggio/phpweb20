<?php
class Zend_View_Helper_Breadcrumbs extends Zend_View_Helper_Abstract
{
	public function breadcrumbs($params){
		
		 $defaultParams = array('trail'     => array(),
	                            'separator' => ' &gt; ');

        // initialize the parameters
        foreach ($defaultParams as $k => $v) {
            if (!isset($params[$k]))
                $params[$k] = $v;
        }

        $links = array();
        $numSteps = count($params['trail']);
        for ($i = 0; $i < $numSteps; $i++) {
            $step = $params['trail'][$i];


            // build the link if it's set and isn't the last step
            if (strlen($step['link']) > 0 && $i < $numSteps - 1) {
                $links[] = sprintf('<a href="%s" title="%s">%s</a>',
                                   htmlSpecialChars($step['link']),
                                   htmlSpecialChars($step['title']),
                                   htmlSpecialChars($step['title']));
            }
            else {
                // either the link isn't set, or it's the last step
                $links[] = htmlSpecialChars($step['title']);
            }
        }

        // join the links using the specified separator
        return join($params['separator'], $links);
	}
	
}

?>