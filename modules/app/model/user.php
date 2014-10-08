<?php

use THCFrame\Model\Model;
use THCFrame\Security\UserInterface;

/**
 * Description of App_Model_User
 *
 * @author Tomy
 */
class App_Model_User extends Model implements UserInterface
{

    /**
     * @readwrite
     */
    protected $_alias = 'us';

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * @index
     * @unique
     *
     * @validate required, email, max(60)
     * @label email
     */
    protected $_email;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * @index
     *
     * @validate required, min(5), max(250)
     * @label heslo
     */
    protected $_password;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 40
     * @unique
     *
     * @validate max(40)
     */
    protected $_salt;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *
     * @validate alphanumeric, max(30)
     * @label uživatelské jméno
     */
    protected $_username;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     * 
     * @validate required, alpha, max(25)
     * @label role
     */
    protected $_role;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *
     * @validate required, alpha, min(3), max(30)
     * @label jméno
     */
    protected $_firstname;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *
     * @validate required, alpha, min(3), max(30)
     * @label příjmení
     */
    protected $_lastname;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_lastLogin;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *
     * @validate numeric, max(30)
     */
    protected $_loginLockdownTime;

    /**
     * @column
     * @readwrite
     * @type tinyint
     *
     * @validate numeric, max(2)
     */
    protected $_loginAttempCounter;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label photo path
     */
    protected $_imgMain;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label thumb path
     */
    protected $_imgThumb;
    
    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;
    
    /**
     * @readwrite
     */
    protected $_activeDog;
    
    /**
     * @readwrite
     */
    protected $_allDogs;

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @param type $datetime
     */
    public function setLastLogin($datetime)
    {
        $this->_lastLogin = $datetime;
    }
    
    /**
     * 
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_imgMain)) {
                return APP_PATH . $this->_imgMain;
            } elseif (file_exists('.' . $this->_imgMain)) {
                return '.' . $this->_imgMain;
            } elseif (file_exists('./' . $this->_imgMain)) {
                return './' . $this->_imgMain;
            }
        } else {
            return $this->_imgMain;
        }
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_imgThumb)) {
                return APP_PATH . $this->_imgThumb;
            } elseif (file_exists('.' . $this->_imgThumb)) {
                return '.' . $this->_imgThumb;
            } elseif (file_exists('./' . $this->_imgThumb)) {
                return './' . $this->_imgThumb;
            }
        } else {
            return $this->_imgThumb;
        }
    }
    
    /**
     * 
     * @param type $value
     * @throws \THCFrame\Security\Exception\Role
     */
    public function setRole($value)
    {
        $role = strtolower(substr($value, 0, 5));
        if ($role != 'role_') {
            throw new \THCFrame\Security\Exception\Role(sprintf('Role %s is not valid', $value));
        } else {
            $this->_role = $value;
        }
    }

    /**
     * 
     */
    public function isActive()
    {
        return (boolean) $this->_active;
    }

    /**
     * 
     * @return type
     */
    public function getWholeName()
    {
        return $this->_firstname . ' ' . $this->_lastname;
    }

    /**
     * 
     * @return type
     */
    public function __toString()
    {
        $str = "Id: {$this->_id} <br/>Email: {$this->_email} <br/> Name: {$this->_firstname} {$this->_lastname}";
        return $str;
    }
    
    /**
     * 
     * @return type
     */
    public static function fetchMembersWithDogs()
    {
        $users = self::all(array('role = ?' => 'role_member', 'active = ?' => true));
        
        if (!empty($users)) {
            foreach ($users as $i => $user) {
                $users[$i] = $user->getUserById();
            }
        }
        
        return $users;
    }
    
    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchUserById($id)
    {
        $user = self::first(array('id = ?' => (int)$id));
        return $user->getUserById();
    }
    
    /**
     * 
     * @return \App_Model_User
     */
    public function getUserById()
    {
        $this->_activeDog = App_Model_Dog::fetchActiveDogByUserId($this->getId());
        $this->_allDogs = App_Model_Dog::fetchOtherDogsByUserId($this->getId());
        
        return $this;
    }
}
