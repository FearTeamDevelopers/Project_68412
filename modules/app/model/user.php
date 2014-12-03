<?php

use THCFrame\Security\Model\BasicUser;

/**
 * 
 */
class App_Model_User extends BasicUser
{

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
     * @readwrite
     */
    protected $_activeDog;
    
    /**
     * @readwrite
     */
    protected $_allDogs;

    /**
     * 
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type && !empty($this->_imgMain)) {
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
        if ($type && !empty($this->_imgThumb)) {
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
