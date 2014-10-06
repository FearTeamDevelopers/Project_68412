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
     * @type integer
     * 
     * @validate numeric, max(8)
     */
    protected $_avatarPhotoId;

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
        $galleryQuery = self::getQuery(array('gl.*'))
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', 
                        array('ph.imgMain', 'ph.imgThumb'))
                ->where('gl.id = ?', (int) $id);
        $galleryArr = self::initialize($galleryQuery);

        if (!empty($galleryArr)) {
            $gallery = array_shift($galleryArr);
            return $gallery->getGalleryById();
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchActivePublicGalleryByUrlkey($urlkey)
    {
        $galleryQuery = self::getQuery(array('gl.*'))
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', 
                        array('ph.imgMain', 'ph.imgThumb'))
                ->where('gl.urlKey = ?', $urlkey)
                ->where('gl.active = ?', true)
                ->where('gl.isPublic = ?', true);
        $galleryArr = self::initialize($galleryQuery);

        if (!empty($galleryArr)) {
            $gallery = array_shift($galleryArr);
            return $gallery->getGalleryById();
        } else {
            return null;
        }
    }
    
    /**
     * 
     * @param type $year
     */
    public static function fetchGalleriesByYear($year)
    {
        $startDate = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year));
        $endDate = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year + 1));
        
        $galleryQuery = self::getQuery(array('gl.*'))
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', 
                        array('ph.imgMain', 'ph.imgThumb'))
                ->wheresql('gl.active=1 AND gl.isPublic=1 AND gl.showDate BETWEEN \''.$startDate.'\' AND \''.$endDate.'\'')
                ->order('gl.showDate', 'DESC');

        $galleries = self::initialize($galleryQuery);

        if (!empty($galleries)) {
            foreach ($galleries as $i => $gallery) {
                $galleries[$i] = $gallery->getGalleryById();
            }
            
            return $galleries;
        } else {
            return null;
        }
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
