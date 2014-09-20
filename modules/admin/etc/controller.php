<?php

namespace Admin\Etc;

use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;
use THCFrame\Core\StringMethods;

/**
 * Description of Controller
 *
 * @author Tomy
 */
class Controller extends BaseController
{

    private $_security;
    
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
    const ERROR_MESSAGE_3 = 'Vyskytla se neznámá chyby';
    const ERROR_MESSAGE_4 = 'Na tuto operaci nemáte oprávnění';
    const ERROR_MESSAGE_5 = 'Povinná pole nejsou validní';
    const ERROR_MESSAGE_6 = 'Přísput odepřen';

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
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');
        $user = $this->getUser();
        
        if (!$user) {
            $this->_security->logout();
            self::redirect('/login');
        }

        if ($session->get('lastActive') > time() - 1800) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage('You has been logged out for long inactivity');
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * @protected
     */
    public function _member()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_member') !== true) {
            $view = $this->getActionView();
            $view->infoMessage(self::ERROR_MESSAGE_6);
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * @protected
     */
    public function _admin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_admin') !== true) {
            $view = $this->getActionView();
            $view->infoMessage(self::ERROR_MESSAGE_6);
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_admin') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @protected
     */
    public function _superadmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') !== true) {
            $view = $this->getActionView();
            $view->infoMessage(self::ERROR_MESSAGE_6);
            $this->_security->logout();
            self::redirect('/login');
        }
    }

    /**
     * 
     * @return boolean
     */
    protected function isSuperAdmin()
    {
        if ($this->_security->getUser() && $this->_security->isGranted('role_superadmin') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_security = Registry::get('security');

        // schedule disconnect from database 
        Events::add('framework.controller.destruct.after', function($name) {
            $database = Registry::get('database');
            $database->disconnect();
        });
    }

    /**
     * 
     */
    public function mutliSubmissionProtectionToken()
    {
        $session = Registry::get('session');
        $token = $session->get('submissionprotection');

        if ($token === null) {
            $token = md5(microtime());
            $session->set('submissionprotection', $token);
        }

        return $token;
    }

    /**
     * 
     * @param type $token
     */
    public function checkMutliSubmissionProtectionToken($token)
    {
        $session = Registry::get('session');
        $sessionToken = $session->get('submissionprotection');

        if ($token == $sessionToken) {
            $session->erase('submissionprotection');
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 
     * @return type
     */
    public function revalidateMutliSubmissionProtectionToken()
    {
        $session = Registry::get('session');
        $session->erase('submissionprotection');
        $token = md5(microtime());
        $session->set('submissionprotection', $token);
        
        return $token;
    }

    /**
     * 
     */
    public function checkToken()
    {
        if($this->_security->checkCsrfToken(RequestMethods::post('tk'))){
            return true;
        }else{
            return false;
        }
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
        $user = $this->getUser();

        if ($view) {
            $view->set('authUser', $this->getUser());

            if ($user) {
                $view->set('isAdmin', $this->isAdmin())
                        ->set('isSuperAdmin', $this->isSuperAdmin())
                        ->set('token', $this->_security->getCsrfToken());
            }
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->getUser());

            if ($user) {
                $layoutView->set('isAdmin', $this->isAdmin())
                        ->set('isSuperAdmin', $this->isSuperAdmin())
                        ->set('token', $this->_security->getCsrfToken());
            }
        }

        parent::render();
    }

}
