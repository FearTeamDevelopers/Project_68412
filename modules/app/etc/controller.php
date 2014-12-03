<?php

namespace App\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;

/**
 * Module specific controller class extending framework controller class
 *
 */
class Controller extends BaseController
{

    /**
     * Store security context object
     * @var type 
     * @read
     */
    protected $_security;
    
    /**
     * Store initialized cache object
     * @var type 
     * @read
     */
    protected $_cache;
    
    /**
     * Store server host name
     * @var type 
     * @read
     */
    protected $_serverHost;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });

        $this->_security = Registry::get('security');
        $this->_serverHost = RequestMethods::server('HTTP_HOST');
        $this->_cache = Registry::get('cache');
        $cfg = Registry::get('configuration');

        $links = $this->getCache()->get('links');

        if ($links !== null) {
            $links = $links;
        } else {
            $links = \App_Model_Link::all(array('active = ?' => true));
            $this->getCache()->set('links', $links);
        }

        $metaData = $this->getCache()->get('global_meta_data');

        if ($metaData !== null) {
            $metaData = $metaData;
        } else {
            $metaData = array(
                'metadescription' => $cfg->meta_description,
                'metarobots' => $cfg->meta_robots,
                'metatitle' => $cfg->meta_title,
                'metaogurl' => $cfg->meta_og_url,
                'metaogtype' => $cfg->meta_og_type,
                'metaogimage' => $cfg->meta_og_image,
                'metaogsitename' => $cfg->meta_og_site_name
            );

            $this->getCache()->set('global_meta_data', $metaData);
        }

        $this->getLayoutView()
                ->set('links', $links)
                ->set('metatitle', $metaData['metatitle'])
                ->set('metarobots', $metaData['metarobots'])
                ->set('metadescription', $metaData['metadescription'])
                ->set('metaogurl', $metaData['metaogurl'])
                ->set('metaogtype', $metaData['metaogtype'])
                ->set('metaogimage', $metaData['metaogimage'])
                ->set('metaogsitename', $metaData['metaogsitename']);
    }

    /**
     * 
     * @param type $string
     * @return type
     */
    protected function _createUrlKey($string)
    {
        $string = StringMethods::removeDiacriticalMarks($string);
        $string = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|', ' '), '-', $string);
        $string = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '°', '´', '`', '%', "'", '"'), '', $string);
        $string = trim($string);
        $string = trim($string, '-');
        return strtolower($string);
    }

    /**
     * load user from security context
     */
    public function getUser()
    {
        return $this->_security->getUser();
    }

    /**
     * 
     */
    public function render()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        if ($view) {
            $view->set('authUser', $this->getUser())
                    ->set('env', ENV);
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->getUser())
                    ->set('env', ENV);
        }

        parent::render();
    }

}
