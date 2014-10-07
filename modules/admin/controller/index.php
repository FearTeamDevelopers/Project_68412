<?php

use Admin\Etc\Controller;

/**
 * Description of Admin_Controller_Index
 *
 * @author Tomy
 */
class Admin_Controller_Index extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        
        $latestnews = App_Model_News::all(
                array('active = ?' => true), 
                array('author', 'title', 'shortBody', 'created'), 
                array('created' => 'DESC'), 8
        );
        
        $latestgallery = App_Model_Gallery::all(
                array('active = ?' => true), 
                array('title', 'created', 'isPublic'), 
                array('created' => 'DESC'), 10
        );
        
        $latestmembers = App_Model_User::all(
                array('active = ?' => true, 'role = ?' => 'role_member'), 
                array('firstname', 'lastname', 'imgThumb', 'created'),
                array('created' => 'DESC'), 10
        );
        
        $latestdogs = App_Model_Dog::fetchAllLimit();
        
        $view->set('latestnews', $latestnews)
                ->set('latestgallery', $latestgallery)
                ->set('latestmembers', $latestmembers)
                ->set('latestdogs', $latestdogs);
    }

}
