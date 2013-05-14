<?php
class Profile_BlogPost extends Profile
{
	public function __construct($db, $id_post = null){
        parent::__construct($db, 'blog_posts_profile');
        if ($id_post > 0)
            $this->setPostId($id_post);
    }

    public function setPostId($id_post){
       $filters = array('id_post' => (int) $id_post);
       $this->_filters = $filters;
    }
}
?>