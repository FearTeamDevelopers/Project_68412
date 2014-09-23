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
                'imgMain' => trim($uploadedFile->file->path, '.'),
                'imgThumb' => trim($uploadedFile->thumb->path, '.')
            ));

            if ($dog->validate()) {
                $id = $dog->save();

                Event::fire('admin.log', array('success', 'Dog Id: ' . $id));
                $view->successMessage('Pes' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/dog/');
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
                'imgMain' => trim($uploadedFile->file->path, '.'),
                'imgThumb' => trim($uploadedFile->thumb->path, '.')
            ));

            if (empty($errors) && $dog->validate()) {
                $id = $dog->save();

                $dp->save();
                Event::fire('admin.log', array('success', 'Dog Id: ' . $id));
                echo $id;
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

                $dog->imgMain = trim($uploadedFile->file->path, '.');
                $dog->imgThumb = trim($uploadedFile->thumb->path, '.');
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
}
