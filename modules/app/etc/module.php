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
            'action' => 'members'
        ),
        array(
            'pattern' => '/galerie',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'gallery'
        ),
        array(
            'pattern' => '/galerie/:year',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'gallery',
            'args' => ':year'
        ),
        array(
            'pattern' => '/aktuality',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index'
        ),
        array(
            'pattern' => '/historie',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'history',
        ),array(
            'pattern' => '/akce',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'akce',
        ),
        array(
            'pattern' => '/aktuality/p/:page',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index',
            'args' => ':page'
        ),
        array(
            'pattern' => '/aktuality/r/:urlkey',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/admin',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index'
        )
    );

}
