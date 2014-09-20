<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Contest
 *
 * @author Tomy
 */
class App_Model_Contest extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'cn';

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
     * @type text
     * @length 150
     * 
     * @validate required, alphanumeric, max(150)
     * @label název
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric, max(10000)
     * @label popis
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     * 
     * @validate date, max(15)
     * @label datum
     */
    protected $_dateStart;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * 
     * @validate alphanumeric, max(100)
     * @label místo
     */
    protected $_location;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate alphanumeric, max(150)
     * @label organizátor
     */
    protected $_organizer;

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

}
