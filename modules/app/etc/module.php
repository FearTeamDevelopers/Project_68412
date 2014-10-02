<?php

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings
 *
 * @author Tomy
 */
class App_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'App';

    /**
     * @read
     */
    protected $_observerClass = '';
    protected $_routes = array(
        array(
            'pattern' => '/clenove',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'users',
        ),array(
            'pattern' => '/galerie',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'galery',
        ),array(
            'pattern' => '/aktuality',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'news',
        ),array(
            'pattern' => '/historie',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'history',
        ),
        array(
            'pattern' => '/news/:page',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index',
            'args' => ':page'
        ),
        array(
            'pattern' => '/admin',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index',
        )
    );

}
