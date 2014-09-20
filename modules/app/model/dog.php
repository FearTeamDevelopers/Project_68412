<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Dog
 *
 * @author Tomy
 */
class App_Model_Dog extends Model
{

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
     * @return array
     */
    public static function fetchAll()
    {
        $query = App_Model_Dog::getQuery(array('do.*'))
                ->join('tb_dogphoto', 'dp.dogId = do.id', 'dp', 
                        array('dp.photoId', 'dp.statusMain'))
                ->join('tb_photo', 'dp.photoId = ph.id', 'ph', 
                        array('ph.imgThumb'))
                ->join('tb_doguser', 'du.dogId = do.id', 'du', 
                        array('du.userId'))
                ->join('tb_user', 'du.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'));
        
        $dogs = App_Model_Dog::initialize($query);
        return $dogs;
    }
}
