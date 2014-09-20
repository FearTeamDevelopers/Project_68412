<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_DogUser
 *
 * @author Tomy
 */
class App_Model_DogUser extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'du';

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
    protected $_statusMain;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     * @unique
     * 
     * @validate required, numeric, max(8)
     */
    protected $_dogId;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     * @unique
     * 
     * @validate required, numeric, max(8)
     */
    protected $_userId;

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
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

}
