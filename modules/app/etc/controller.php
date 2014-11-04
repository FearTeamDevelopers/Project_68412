<?php

namespace App\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Core\StringMethods;

/**
 * Module specific controller class extending framework controller class
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    private $_security;
    
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
        $cache = Registry::get('cache');
        
        $links = $cache->get('links');

        if (NULL !== $links) {
            $links = $links;
        } else {
            $links = \App_Model_Link::all(array('active = ?' => true));
            $cache->set('links', $links);
        }

        $metaData = $cache->get('global_meta_data');

        if (NULL !== $metaData) {
            $metaData = $metaData;
        } else {
            $metaData = array(
                'metadescription' => $this->loadConfigFromDb('meta_description'),
                'metarobots' => $this->loadConfigFromDb('meta_robots'),
                'metatitle' => $this->loadConfigFromDb('meta_title'),
                'metaogurl' => $this->loadConfigFromDb('meta_og_url'),
                'metaogtype' => $this->loadConfigFromDb('meta_og_type'),
                'metaogimage' => $this->loadConfigFromDb('meta_og_image'),
                'metaogsitename' => $this->loadConfigFromDb('meta_og_site_name')
            );

            $cache->set('global_meta_data', $metaData);
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
