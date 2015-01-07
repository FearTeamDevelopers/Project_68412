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

    const SUCCESS_MESSAGE_1 = ' byl(a) úspěšně vytovřen(a)';
    const SUCCESS_MESSAGE_2 = 'Všechny změny byly úspěšně uloženy';
    const SUCCESS_MESSAGE_3 = ' byl(a) úspěšně smazán(a)';
    const SUCCESS_MESSAGE_4 = 'Vše bylo úspěšně aktivováno';
    const SUCCESS_MESSAGE_5 = 'Vše bylo úspěšně deaktivováno';
    const SUCCESS_MESSAGE_6 = 'Vše bylo úspěšně smazáno';
    const SUCCESS_MESSAGE_7 = 'Vše bylo úspěšně nahráno';
    const SUCCESS_MESSAGE_8 = 'Vše bylo úspěšně uloženo';
    const SUCCESS_MESSAGE_9 = 'Vše bylo úspěšně přidáno';
    const ERROR_MESSAGE_1 = 'Oops, něco se pokazilo';
    const ERROR_MESSAGE_2 = 'Nenalezeno';
    const ERROR_MESSAGE_3 = 'Nastala neznámá chyby';
    const ERROR_MESSAGE_4 = 'Na tuto operaci nemáte oprávnění';
    const ERROR_MESSAGE_5 = 'Povinná pole nejsou validní';
    const ERROR_MESSAGE_6 = 'Přísput odepřen';
    
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
        $neutralChars = array('.', ',', '_', '(', ')', '[', ']', '|', ' ');
        $preCleaned = StringMethods::fastClean($string, $neutralChars, '-');
        $cleaned = StringMethods::fastClean($preCleaned);
        $return = trim(trim($cleaned), '-');
        return strtolower($return);
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
