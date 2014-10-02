<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;
use THCFrame\Model\Model;

class App_Controller_Index extends Controller
{
    public function index()
    {
        
    }
    
    public function users()
    {
        $view = $this->getActionView();
        
        $users = App_Model_User::all(array('role = ?' => 'role_member', 'active = ?' => true));
        
        foreach ($users as $i => $user){
            $dog = App_Model_Dog::fetchActiveDogsByUserId($user->getId());
            $user->setActiveDog($dog);
            $users[$i] = $user;
           
        }
        $view->set('users', $users);
    }
}
