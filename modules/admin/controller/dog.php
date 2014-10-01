<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Core\ArrayMethods;

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
        
        $users = App_Model_User::all(
                array('role = ?' => 'role_member'), 
                array('id', 'firstname', 'lastname')
        );
        
        $view->set('submstoken', $this->mutliSubmissionProtectionToken())
                ->set('users', $users);

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
                foreach ($data['files'] as $file) {
                    $mainPhoto = ArrayMethods::toObject($file);
                    break;
                }
            } catch (Exception $ex) {
                $errors['dogphoto'] = array($ex->getMessage());
            }

            $dog = new App_Model_Dog(array(
                'userId' => RequestMethods::post('user'),
                'isActive' => RequestMethods::post('isact', 1),
                'dogName' => RequestMethods::post('dogname'),
                'race' => RequestMethods::post('dograce'),
                'dob' => RequestMethods::post('dogdob'),
                'information' => RequestMethods::post('doginfo'),
                'imgMain' => trim($mainPhoto->file->path, '.'),
                'imgThumb' => trim($mainPhoto->thumb->path, '.')
            ));

            if (empty($errors) && $dog->validate()) {
                $dogId = $dog->save();

                if (RequestMethods::post('uploadmorephotos') == '1') {
                    try {
                        $additionalPhotos = $fileManager->upload('secondfile', 'dog', time() . '_');
                    } catch (Exception $ex) {
                        $errors['secondfile'] = array($ex->getMessage());
                    }

                    if (empty($additionalPhotos['errors']) && empty($errors['secondfile'])) {
                        foreach ($additionalPhotos['files'] as $i => $value) {
                            $uploadedFile = ArrayMethods::toObject($value);

                            $photo = new App_Model_Photo(array(
                                'galleryId' => 2,
                                'imgMain' => trim($uploadedFile->file->path, '.'),
                                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                                'description' => RequestMethods::post('description'),
                                'photoName' => $uploadedFile->file->filename,
                                'mime' => $uploadedFile->file->ext,
                                'sizeMain' => $uploadedFile->file->size,
                                'sizeThumb' => $uploadedFile->thumb->size
                            ));

                            if ($photo->validate()) {
                                $photoId = $photo->save();

                                $dp = new App_Model_DogPhoto(array(
                                    'dogId' => $dogId,
                                    'photoId' => $photoId
                                ));
                                $dp->save();

                                Event::fire('admin.log', array('success', 'Photo id: ' . $photoId));
                            } else {
                                Event::fire('admin.log', array('fail'));
                                $errors['secondfile'][] = $photo->getErrors();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('success', 'Dog Id: ' . $dogId));
                        $view->successMessage('Pes' . self::SUCCESS_MESSAGE_1);
                        self::redirect('/admin/dog/');
                    } else {
                        Event::fire('admin.log', array('fail'));
                        $view->set('errors', $errors)
                                ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                                ->set('dog', $dog);
                    }
                } else {
                    Event::fire('admin.log', array('success', 'Dog Id: ' . $dogId));
                    $view->successMessage('Pes' . self::SUCCESS_MESSAGE_1);
                    self::redirect('/admin/dog/');
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
    public function edit($id)
    {
        $view = $this->getActionView();

        $dog = App_Model_Dog::first(array('id = ?' => (int) $id));

        if ($dog === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/dog/');
        }
        
        $photos = App_Model_Photo::fetchPhotosByDogId($dog->getId());
        $users = App_Model_User::all(
                array('role = ?' => 'role_member'), 
                array('id', 'firstname', 'lastname')
        );

        $view->set('dog', $dog)
                ->set('users', $users)
                ->set('photos', $photos);

        if (RequestMethods::post('submitEditDog')) {
            if ($this->checkToken() !== true) {
                self::redirect('/admin/dog/');
            }
            $errors = array();

            if ($dog->imgMain == '') {
                try {
                    $fileManager = new FileManager(array(
                        'thumbWidth' => $this->loadConfigFromDb('thumb_width'),
                        'thumbHeight' => $this->loadConfigFromDb('thumb_height'),
                        'thumbResizeBy' => $this->loadConfigFromDb('thumb_resizeby'),
                        'maxImageWidth' => $this->loadConfigFromDb('photo_maxwidth'),
                        'maxImageHeight' => $this->loadConfigFromDb('photo_maxheight')
                    ));

                    try {
                        $data = $fileManager->upload('mainfile', 'dog', time() . '_');
                        $uploadedFile = ArrayMethods::toObject($data);
                        $imgMain = trim($uploadedFile->file->path, '.');
                        $imgThumb = trim($uploadedFile->thumb->path, '.');
                    } catch (Exception $ex) {
                        $errors['mainfile'] = array($ex->getMessage());
                    }
                } catch (Exception $ex) {
                    $errors['mainfile'] = $ex->getMessage();
                }
            } else {
                $imgMain = $dog->imgMain;
                $imgThumb = $dog->imgThumb;
            }

            $dog->userId = RequestMethods::post('user');
            $dog->isActive = RequestMethods::post('isActive', 1);
            $dog->dogName = RequestMethods::post('dogname');
            $dog->race = RequestMethods::post('dograce');
            $dog->dob = RequestMethods::post('dogdob');
            $dog->information = RequestMethods::post('doginfo');
            $dog->active = RequestMethods::post('active');
            $dog->imgMain = $imgMain;
            $dog->imgThumb = $imgThumb;

            if (empty($errors) && $dog->validate()) {
                $dog->save();
                
                if (RequestMethods::post('uploadmorephotos') == '1') {
                    try {
                        $additionalPhotos = $fileManager->upload('secondfile', 'dog', time() . '_');
                    } catch (Exception $ex) {
                        $errors['secondfile'] = array($ex->getMessage());
                    }

                    if (empty($additionalPhotos['errors']) && empty($errors['secondfile'])) {
                        foreach ($additionalPhotos['files'] as $i => $value) {
                            $uploadedFile = ArrayMethods::toObject($value);

                            $photo = new App_Model_Photo(array(
                                'galleryId' => 2,
                                'imgMain' => trim($uploadedFile->file->path, '.'),
                                'imgThumb' => trim($uploadedFile->thumb->path, '.'),
                                'description' => RequestMethods::post('description'),
                                'photoName' => $uploadedFile->file->filename,
                                'mime' => $uploadedFile->file->ext,
                                'sizeMain' => $uploadedFile->file->size,
                                'sizeThumb' => $uploadedFile->thumb->size
                            ));

                            if ($photo->validate()) {
                                $photoId = $photo->save();

                                $dp = new App_Model_DogPhoto(array(
                                    'dogId' => $dog->getId(),
                                    'photoId' => $photoId
                                ));
                                $dp->save();

                                Event::fire('admin.log', array('success', 'Photo id: ' . $photoId));
                            } else {
                                Event::fire('admin.log', array('fail'));
                                $errors['secondfile'][] = $photo->getErrors();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('success', 'Dog Id: ' . $id));
                        $view->successMessage(self::SUCCESS_MESSAGE_2);
                        self::redirect('/admin/dog/');
                    } else {
                        Event::fire('admin.log', array('fail'));
                        $view->set('errors', $errors)
                                ->set('dog', $dog);
                    }
                } else {
                    Event::fire('admin.log', array('success', 'Dog Id: ' . $id));
                    $view->successMessage(self::SUCCESS_MESSAGE_2);
                    self::redirect('/admin/dog/');
                }
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
                    echo 'success';
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
     * @param type $id
     */
    public function deleteMainPhoto($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkToken()) {
            $dog = App_Model_Dog::first(
                            array('id = ?' => (int) $id), array('id', 'imgMain', 'imgThumb')
            );

            if (NULL === $dog) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if (unlink($dog->getUnlinkPath()) && unlink($dog->getUnlinkThumbPath())) {
                    $dog->imgMain = '';
                    $dog->imgThumb = '';
                    $dog->save();

                    Event::fire('admin.log', array('success', 'Dog Id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Dog Id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
