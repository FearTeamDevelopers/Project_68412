<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Gallery extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_Gallery::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Action method returns list of all galleries
     * 
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $galleries = App_Model_Gallery::all();
        $view->set('galleries', $galleries);
    }

    /**
     * Action method shows and processes form used for new gallery creation
     * 
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddGallery')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/gallery/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Galerie s tímto názvem již existuje');
            }

            $gallery = new App_Model_Gallery(array(
                'title' => RequestMethods::post('title'),
                'avatarPhotoId' => 0,
                'isPublic' => RequestMethods::post('public', 1),
                'showDate' => RequestMethods::post('showdate', date('Y-m-d')),
                'urlKey' => $urlKey,
                'description' => RequestMethods::post('description', '')
            ));

            if (empty($errors) && $gallery->validate()) {
                $id = $gallery->save();

                Event::fire('admin.log', array('success', 'Gallery id: ' . $id));
                $view->successMessage('Galerie' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/gallery/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('gallery', $gallery)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $errors + $gallery->getErrors());
            }
        }
    }

    /**
     * Method shows detail of specific collection based on param id. 
     * From here can user upload photos and videos into collection.
     * 
     * @before _secured, _admin
     * @param int $id   collection id
     */
    public function detail($id)
    {
        $view = $this->getActionView();

        $gallery = App_Model_Gallery::fetchGalleryById($id);

        if ($gallery === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/gallery/');
        }

        $view->set('gallery', $gallery);
    }

    /**
     * Action method shows and processes form used for editing specific 
     * collection based on param id
     * 
     * @before _secured, _admin
     * @param int $id   collection id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $gallery = App_Model_Gallery::fetchGalleryById((int) $id);

        if (NULL === $gallery) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/gallery/');
        }

        $view->set('gallery', $gallery);

        if (RequestMethods::post('submitEditGallery')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/gallery/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($gallery->getUrlKey() !== $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Galerie s tímto názvem již existuje');
            }

            $gallery->title = RequestMethods::post('title');
            $gallery->avatarPhotoId = RequestMethods::post('avatar');
            $gallery->isPublic = RequestMethods::post('public');
            $gallery->showDate = RequestMethods::post('showdate');
            $gallery->active = RequestMethods::post('active');
            $gallery->urlKey = $urlKey;
            $gallery->description = RequestMethods::post('description', '');

            if (empty($errors) && $gallery->validate()) {
                $gallery->save();

                Event::fire('admin.log', array('success', 'Gallery id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/gallery/');
            } else {
                Event::fire('admin.log', array('fail', 'Gallery id: ' . $id));
                $view->set('errors', $gallery->getErrors());
            }
        }
    }

    /**
     * Action method shows and processes form used for deleting specific 
     * collection based on param id. If is collection delete confirmed, 
     * there is option used for deleting all photos in collection.
     * 
     * @before _secured, _admin
     * @param int $id   collection id
     */
    public function delete($id)
    {
        $view = $this->getActionView();

        $gallery = App_Model_Gallery::first(
                        array('id = ?' => (int) $id), array('id', 'title', 'created')
        );

        if (NULL === $gallery) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/gallery/');
        }

        $view->set('gallery', $gallery);

        if (RequestMethods::post('submitDeleteGallery')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/gallery/');
            }

            if (RequestMethods::post('action') == 1) {
                $fm = new FileManager();
                $configuration = Registry::get('config');

                if (!empty($configuration->files)) {
                    $pathToImages = trim($configuration->files->pathToImages, '/');
                    $pathToThumbs = trim($configuration->files->pathToThumbs, '/');
                } else {
                    $pathToImages = 'public/uploads/images';
                    $pathToThumbs = 'public/uploads/images';
                }

                $photos = App_Model_Photo::all(array('galleryId = ?' => (int) $id));

                $ids = array();
                foreach ($photos as $colPhoto) {
                    $ids[] = $colPhoto->getId();
                }

                App_Model_Photo::deleteAll(array('id IN ?' => $ids));

                $path = APP_PATH . '/' . $pathToImages . '/gallery/' . $gallery->getId();
                $pathThumbs = APP_PATH . '/' . $pathToThumbs . '/gallery/' . $gallery->getId();

                if ($path == $pathThumbs) {
                    $fm->remove($path);
                } else {
                    $fm->remove($path);
                    $fm->remove($pathThumbs);
                }
            } elseif (RequestMethods::post('action') == 2) {
                $photos = App_Model_Photo::all(array('galleryId = ?' => $id));
                $ids = array();
                foreach ($photos as $colPhoto) {
                    $ids[] = $colPhoto->getId();
                }

                App_Model_Photo::deleteAll(array('id IN ?' => $ids));
            }

            if ($gallery->delete()) {
                Event::fire('admin.log', array('success', 'Gallery id: ' . $id));
                $view->successMessage('Galerie' . self::SUCCESS_MESSAGE_3);
                self::redirect('/admin/gallery/');
            } else {
                Event::fire('admin.log', array('fail', 'Gallery id: ' . $id));
                $view->warningMessage(self::ERROR_MESSAGE_1);
                self::redirect('/admin/gallery/');
            }
        }
    }

    /**
     * Action method shows and processes form used for uploading photos into
     * collection specified by param id
     * 
     * @before _secured, _admin
     * @param int $id   collection id
     */
    public function addPhoto($id)
    {
        $view = $this->getActionView();

        $gallery = App_Model_Gallery::first(
                        array(
                    'id = ?' => (int) $id,
                    'active = ?' => true
                        ), array('id', 'title')
        );

        if ($gallery === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/gallery/');
        }

        $view->set('gallery', $gallery)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddPhoto')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/gallery/');
            }

            $errors = array();
            $cfg = Registry::get('configuration');

            $fileManager = new FileManager(array(
                'thumbWidth' => $cfg->thumb_width,
                'thumbHeight' => $cfg->thumb_height,
                'thumbResizeBy' => $cfg->thumb_resizeby,
                'maxImageWidth' => $cfg->photo_maxwidth,
                'maxImageHeight' => $cfg->photo_maxheight
            ));

            $fileErrors = $fileManager->uploadImage('secondfile', 'gallery/' . $gallery->getId(), time() . '_')->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $info = $file->getOriginalInfo();

                        $photo = new App_Model_Photo(array(
                            'galleryId' => $gallery->getId(),
                            'imgMain' => trim($file->getFilename(), '.'),
                            'imgThumb' => trim($file->getThumbname(), '.'),
                            'description' => RequestMethods::post('description'),
                            'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                            'mime' => $info['mime'],
                            'format' => $info['format'],
                            'width' => $file->getWidth(),
                            'height' => $file->getHeight(),
                            'size' => $file->getSize()
                        ));

                        if ($photo->validate()) {
                            $aid = $photo->save();

                            Event::fire('admin.log', array('success', 'Photo id: ' . $aid . ' in gallery ' . $gallery->getId()));
                        } else {
                            Event::fire('admin.log', array('fail', 'Photo in gallery ' . $gallery->getId()));
                            $errors['secondfile'][] = $photo->getErrors();
                        }
                    }
                }
            }

            $errors['secondfile'] = $fileErrors;

            if (empty($errors['secondfile'])) {
                $view->successMessage(self::SUCCESS_MESSAGE_7);
                self::redirect('/admin/gallery/detail/' . $gallery->getId());
            } else {
                $view->set('errors', $errors);
            }
        }
    }

    /**
     * Method is called via ajax and deletes photo specified by param id
     * 
     * @before _secured, _admin
     * @param int $id   photo id
     */
    public function deletePhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $photo = App_Model_Photo::first(
                        array('id = ?' => $id), array('id', 'imgMain', 'imgThumb')
        );

        if (null === $photo) {
            echo self::ERROR_MESSAGE_2;
        } else {
            @unlink($photo->getUnlinkPath());
            @unlink($photo->getUnlinkThumbPath());

            if ($photo->delete()) {

                Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Photo id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * Method is called via ajax and activate or deactivate photo specified by
     * param id
     * 
     * @before _secured, _admin
     * @param int $id   photo id
     */
    public function changePhotoStatus($id)
    {
        $this->willRenderLayoutView = false;
        $this->willRenderActionView = false;

        $photo = App_Model_Photo::first(array('id = ?' => $id));

        if (null === $photo) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if (!$photo->active) {
                $photo->active = true;

                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                    echo 'active';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            } elseif ($photo->active) {
                $photo->active = false;
                if ($photo->validate()) {
                    $photo->save();
                    Event::fire('admin.log', array('success', 'Photo id: ' . $id));
                    echo 'inactive';
                } else {
                    echo join('<br/>', $photo->getErrors());
                }
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;
        
        $galleries = App_Model_Gallery::all(
                array('isPublic = ?' => 1, 'active = ?' => true)
        );
        
        $view->set('galleries', $galleries);
    }
    
}
