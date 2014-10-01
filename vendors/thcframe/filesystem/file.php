<?php

namespace THCFrame\Filesystem;

use THCFrame\Core\Base as Base;

/**
 * 
 */
class File extends Base
{

    /**
     * @readwrite
     */
    protected $_path;

    /**
     * @readwrite
     */
    protected $_filename;

    /**
     * @readwrite
     */
    protected $_size;

    /**
     * @readwrite
     */
    protected $_ext;

    /**
     * @readwrite
     */
    protected $_modificationTime;

    /**
     * @readwrite
     */
    protected $_accessTime;

    /**
     * @readwrite
     */
    protected $_isExecutable;

    /**
     * @readwrite
     */
    protected $_isReadable;

    /**
     * @readwrite
     */
    protected $_isWritable;

    /**
     * 
     * @param type $options
     */
    public function __construct($file)
    {
        parent::__construct();

        $this->_path = $file;
        $this->_loadMetaData();
    }

    /**
     * 
     */
    protected function _loadMetaData()
    {
        clearstatcache();

        $this->_pathname = pathinfo($this->_path, PATHINFO_FILENAME);
        $this->_ext = strtolower(pathinfo($this->_path, PATHINFO_EXTENSION));
        $this->_size = filesize($this->_path);
        $this->_modificationTime = filemtime($this->_path);
        $this->_accessTime = fileatime($this->_path);
        $this->_isExecutable = is_executable($this->_path);
        $this->_isReadable = is_readable($this->_path);
        $this->_isWritable = is_writable($this->_path);
    }

}
