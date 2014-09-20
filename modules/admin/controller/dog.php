<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;

/**
 * Description of Admin_Controller_Dog
 *
 * @author Tomy
 */
class Admin_Controller_Dog extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $dogs = App_Model_Dog::fetchAll();
        $view->set('dogs', $dogs);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddDog')) {
            if ($this->checkToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/dog/');
            }

            $errors = array();

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
            ));

            try {
                $data = $fileManager->upload('dogphoto', 'dog', time() . '_');
                $uploadedFile = ArrayMethods::toObject($data);
            } catch (Exception $ex) {
                $errors['dogphoto'] = array($ex->getMessage());
            }

            $dog = new App_Model_Dog(array(
                'isActive' => RequestMethods::post('isact'),
                'dogName' => RequestMethods::post('dogname'),
                'race' => RequestMethods::post('race'),
                'dob' => RequestMethods::post('dob'),
                'information' => RequestMethods::post('info'),
            ));

            if ($dog->validate()) {
                $id = $dog->save();

                $photo = new App_Model_Photo(array(
                    'galleryId' => 2,
                    'description' => RequestMethods::post('photoDesc'),
                    'imgMain' => trim($uploadedFile->file->path, '.'),
                    'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                    'photoName' => $uploadedFile->file->filename,
                    'mime' => $uploadedFile->file->ext,
                    'sizeMain' => $uploadedFile->file->size,
                    'sizeThumb' => $uploadedFile->thumb->size
                ));

                if ($photo->validate()) {
                    $photoId = $photo->save();

                    $dp = new App_Model_DogPhoto(array(
                        'statusMain' => 1,
                        'dogId' => $id,
                        'photoId' => $photoId
                    ));

                    $dp->save();

                    Event::fire('admin.log', array('success', 'Dog Id: ' . $id));
                    $view->successMessage('Pes' . self::SUCCESS_MESSAGE_1);
                    self::redirect('/admin/dog/');
                } else {
                    Event::fire('admin.log', array('fail'));
                    $view->set('errors', $errors + $photo->getErrors())
                            ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                            ->set('dog', $dog);
                }
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $dog->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('dog', $dog);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function addAjax()
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddDogAj')) {
            if ($this->checkToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                echo self::ERROR_MESSAGE_1;
            }

            $errors = array();

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
            ));

            try {
                $data = $fileManager->upload('dogphoto', 'dog', time() . '_');
                $uploadedFile = ArrayMethods::toObject($data);
            } catch (Exception $ex) {
                $errors['dogphoto'] = array($ex->getMessage());
            }

            $dog = new App_Model_Dog(array(
                'isActive' => RequestMethods::post('isact'),
                'dogName' => RequestMethods::post('dogname'),
                'race' => RequestMethods::post('race'),
                'dob' => RequestMethods::post('dob'),
                'information' => RequestMethods::post('info'),
            ));

            if (empty($errors) && $dog->validate()) {
                $id = $dog->save();

                $photo = new App_Model_Photo(array(
                    'galleryId' => 2,
                    'description' => RequestMethods::post('photoDesc'),
                    'imgMain' => trim($uploadedFile->file->path, '.'),
                    'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                    'photoName' => $uploadedFile->file->filename,
                    'mime' => $uploadedFile->file->ext,
                    'sizeMain' => $uploadedFile->file->size,
                    'sizeThumb' => $uploadedFile->thumb->size
                ));

                if ($photo->validate()) {
                    $photoId = $photo->save();

                    $dp = new App_Model_DogPhoto(array(
                        'statusMain' => 1,
                        'dogId' => $id,
                        'photoId' => $photoId
                    ));

                    $dp->save();
                    Event::fire('admin.log', array('success', 'Dog Id: ' . $id));
                    echo $id;
                } else {
                    Event::fire('admin.log', array('fail'));
                    echo self::ERROR_MESSAGE_5;
                }
            } else {
                Event::fire('admin.log', array('fail'));
                echo self::ERROR_MESSAGE_5;
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $dog = App_Model_Dog::first(array('id = ?' => (int) $id));

        if ($dog === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/dog/');
        }

        $view->set('dog', $dog);

        if (RequestMethods::post('submitEditDog')) {
            if ($this->checkToken() !== true) {
                self::redirect('/admin/dog/');
            }
            $errors = array();

            if (RequestMethods::post('updateDogMainPhoto') == '1') {

                $fileManager = new FileManager(array(
                    'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                    'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                    'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                    'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                    'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                ));

                try {
                    $data = $fileManager->upload('dogphoto', 'dog', time() . '_');
                    $uploadedFile = ArrayMethods::toObject($data);
                } catch (Exception $ex) {
                    $errors['dogphoto'] = array($ex->getMessage());
                }

                $photo = new App_Model_Photo(array(
                    'galleryId' => 2,
                    'description' => RequestMethods::post('photoDesc'),
                    'imgMain' => trim($uploadedFile->file->path, '.'),
                    'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                    'photoName' => $uploadedFile->file->filename,
                    'mime' => $uploadedFile->file->ext,
                    'sizeMain' => $uploadedFile->file->size,
                    'sizeThumb' => $uploadedFile->thumb->size
                ));

                if ($photo->validate()) {
                    $photoId = $photo->save();

                    $allDogPhotos = App_Model_DogPhoto::all(array('dogId = ?' => $dog->getId()));

                    foreach ($allDogPhotos as $dogPhoto) {
                        $dogPhoto->statusMain = 0;
                        $dogPhoto->save();
                    }

                    $dp = new App_Model_DogPhoto(array(
                        'statusMain' => 1,
                        'dogId' => $dog->getId(),
                        'photoId' => $photoId
                    ));

                    $dp->save();
                } else {
                    $errors = $errors + $photo->getErrors();
                }
            }

            $dog->isActive = RequestMethods::post('isActive');
            $dog->dogName = RequestMethods::post('dogName');
            $dog->dob = RequestMethods::post('dob');
            $dog->information = RequestMethods::post('info');
            $dog->active = RequestMethods::post('active');

            if (empty($errors) && $dog->validate()) {
                $dog->save();

                Event::fire('admin.log', array('success', 'Dog Id: ' . $dog->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/dog/');
            } else {
                Event::fire('admin.log', array('fail', 'Dog Id: ' . $dog->getId()));
                $view->set('errors', $errors + $dog->getErrors())
                        ->set('dog', $dog);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkToken()) {
            $dog = App_Model_Dog::first(
                            array('id = ?' => (int) $id), array('id')
            );

            if (NULL === $dog) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($dog->delete()) {
                    Event::fire('admin.log', array('success', 'Dog Id: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'Dog Id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _admin
     */
    public function changeMainPhoto($dogId, $photoId)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkToken()) {
            $dogPhoto = App_Model_DogPhoto::first(array('dogId = ?' => (int) $dogId, 'photoId = ?' => (int)$photoId));

            if (NULL === $dogPhoto) {
                echo self::ERROR_MESSAGE_2;
            } else {
                $dogPhoto->statusMain = 1;
                if ($dogPhoto->validate()) {
                    $dogPhoto->save();
                    Event::fire('admin.log', array('success', 'DogPhoto Id: ' . $dogPhoto->getId()));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'DogPhoto Id: ' . $dogPhoto->getId()));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }
}
