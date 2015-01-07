<?php

use THCFrame\Model\Model;

/**
 * 
 */
class App_Model_PageContent extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'co';

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
     * @validate required, alpha, max(150)
     * @label název
     */
    protected $_pageName;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * @unique
     * 
     * @validate required, alpha, max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html
     * @label text
     */
    protected $_body;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate html
     * @label text en
     */
    protected $_bodyEn;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     * @label meta-název
     */
    protected $_metaTitle;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric, max(1000)
     * @label meta-popis
     */
    protected $_metaDescription;

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
