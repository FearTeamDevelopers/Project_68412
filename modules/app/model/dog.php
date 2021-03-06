<?php

use THCFrame\Model\Model;

/**
 * 
 */
class App_Model_Dog extends Model {

    /**
     * @readwrite
     */
    protected $_alias = 'do';

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
     * @type integer
     * @index
     * 
     * @validate required, numeric, max(8)
     */
    protected $_userId;

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
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_isActive;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     *
     * @validate required, alphanumeric, max(100)
     * @label jméno
     */
    protected $_dogName;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *
     * @validate required, alpha, max(30)
     * @label rasa
     */
    protected $_race;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     *
     * @validate date, max(15)
     * @label datum narozní
     */
    protected $_dob;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     *
     * @validate alphanumeric, max(5000)
     * @label informace
     */
    protected $_information;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label foto
     */
    protected $_imgMain;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label náhled
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
    protected $_exams;

    /**
     * @readwrite
     */
    protected $_adPhoto;

    /**
     * 
     */
    public function preSave() {
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
     * @return array
     */
    public static function fetchAll() {
        $query = App_Model_Dog::getQuery(array('do.*'))
                ->leftjoin('tb_user', 'do.userId = us.id', 'us',
                        array('us.firstname', 'us.lastname'));

        $dogs = App_Model_Dog::initialize($query);
        return $dogs;
    }
    
    /**
     * 
     * @return array
     */
    public static function fetchAllLimit() {
        $query = App_Model_Dog::getQuery(array('do.*'))
                ->leftjoin('tb_user', 'do.userId = us.id', 'us',
                        array('us.firstname', 'us.lastname'))
                ->where('do.active = ?', true)
                ->order('do.created', 'DESC')
                ->limit(10);

        $dogs = App_Model_Dog::initialize($query);
        return $dogs;
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchDogById($id)
    {
        $dog = self::first(array('id = ?' => (int)$id));
        return $dog->getDogById();
    }
    
    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchOtherDogsByUserId($id) 
    {
        $dogs = self::all(array(
            'userId = ?' => (int) $id, 
            'isActive = ?' => false)
        );
        
        if (!empty($dogs)) {
            foreach ($dogs as $i => $dog) {
                $dogs[$i] = $dog->getDogById();
            }
        }

        return $dogs;
    }
    
    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchAllDogsByUserId($id)
    {
        $dogs = self::all(array(
            'userId = ?' => (int) $id)
        );
        
        if (!empty($dogs)) {
            foreach ($dogs as $i => $dog) {
                $dogs[$i] = $dog->getDogById();
            }
        }

        return $dogs;
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchActiveDogByUserId($id) 
    {
        $dog = self::first(array(
            'userId = ?' => (int) $id, 
            'isActive = ?' => true)
        );
        
        if($dog !== null){
            return $dog->getDogById();
        }else{
            return null;
        }
    }

    /**
     * 
     * @return \App_Model_Dog
     */
    public function getDogById() {
        $query = App_Model_Exam::getQuery(array('ex.*'))
                ->join('tb_dogexam', 'ex.id = de.examId', 'de',
                        array('de.dogId', 'de.examId'))
                ->where('de.dogId = ?', (int) $this->getId());

        $this->_exams = App_Model_Exam::initialize($query);

        $query2 = App_Model_Photo::getQuery(array('ph.*'))
                ->join('tb_dogphoto', 'ph.id = dp.photoId', 'dp', 
                        array('dp.dogId', 'dp.photoId'))
                ->where('dp.dogId = ?', (int) $this->getId());

        $this->_adPhoto = App_Model_Photo::initialize($query2);

        return $this;
    }
}
    
