<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Gallery
 *
 * @author Tomy
 */
class App_Model_Gallery extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'gl';

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
     * @length 200
     * @unique
     * 
     * @validate required, alphanumeric, max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 20
     * 
     * @validate date, max(20)
     * @label datum
     */
    protected $_showDate;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(30000)
     * @label popis
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(2)
     * @lable přístupnost
     */
    protected $_isPublic;

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
    protected $_photos;

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
     * @param type $id
     * @return type
     */
    public static function fetchGalleryById($id)
    {
        $gallery = self::first(array('id = ?' => (int) $id));
        return $gallery->getGalleryById();
    }

    /**
     * 
     * @return \App_Model_Gallery
     */
    public function getGalleryById()
    {
        $photos = App_Model_Photo::all(array('galleryId = ?' => $this->getId()));

        $this->_photos = $photos;

        return $this;
    }

}
