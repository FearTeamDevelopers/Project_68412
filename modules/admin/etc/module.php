<?php

use THCFrame\Module\Module;

/**
 * 
 */
class Admin_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'Admin';

    /**
     * @read
     */
    protected $_observerClass = 'Admin_Etc_Observer';
    
    /**
     * @read
     * @var array
     */
    protected $_routes = array(
        array(
            'pattern' => '/login',
            'module' => 'admin',
            'controller' => 'user',
            'action' => 'login',
        ),
        array(
            'pattern' => '/logout',
            'module' => 'admin',
            'controller' => 'user',
            'action' => 'logout',
        ),
        array(
            'pattern' => '/admin/dog/deleteexam/:dogid/:examid',
            'module' => 'admin',
            'controller' => 'dog',
            'action' => 'deleteexam',
            'args' => array(':dogid', ':examid')
        )
    );

}
