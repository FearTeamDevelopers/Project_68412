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

        $users = App_Model_User::all(
                        array('role = ?' => 'role_member'), array('id', 'firstname', 'lastname')
        );

        $exams = App_Model_Exam::all(array('active = ?' => true));

        $view->set('submstoken', $this->mutliSubmissionProtectionToken())
                ->set('exams', $exams)
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

            $photoNameRaw = RequestMethods::post('user') . '-' . RequestMethods::post('dogname');
            $photoName = $this->_createUrlKey($photoNameRaw);

            $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $photoName, 'dog', time() . '_')->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                $errors['croppedimage'] = $fileErrors;
            }

            if ((int) RequestMethods::post('isactive') == 1) {
                App_Model_Dog::updateAll(array('isActive = ?' => true, 'userId = ?' => (int) RequestMethods::post('user')), array('isActive' => 0));
            }

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $imgMain = trim($file->getFilename(), '.');
                        $imgThumb = trim($file->getThumbname(), '.');

                        break;
                    }
                }
            }else{
                $imgMain = '';
                $imgThumb = '';
            }

            $dog = new App_Model_Dog(array(
                'userId' => RequestMethods::post('user'),
                'isActive' => RequestMethods::post('isactive', 0),
                'dogName' => RequestMethods::post('dogname'),
                'race' => RequestMethods::post('dograce'),
                'dob' => RequestMethods::post('dogdob'),
                'information' => RequestMethods::post('doginfo'),
                'imgMain' => $imgMain,
                'imgThumb' => $imgThumb
            ));

            if (empty($errors) && $dog->validate()) {
                $dogId = $dog->save();

                $examsArr = (array) RequestMethods::post('chexam');

                if ($examsArr[0] != '') {
                    foreach ($examsArr as $exam) {
                        $de = new App_Model_DogExam(array(
                            'dogId' => (int) $dogId,
                            'examId' => (int) $exam
                        ));

                        $de->save();
                        Event::fire('admin.log', array('success', 'Dog id: ' . $dogId . ' has exam ' . $exam));
                    }
                }

                if (RequestMethods::post('uploadmorephotos') == '1') {
                    $fileErrors = $fileManager->newUpload()->upload('secondfile', 'dog', time() . '_')->getUploadErrors();
                    $files = $fileManager->getUploadedFiles();

                    if (!empty($fileErrors)) {
                        $errors['secondfile'] = $fileErrors;
                    }
                    if (!empty($files)) {
                        foreach ($files as $i => $file) {
                            if ($file instanceof \THCFrame\Filesystem\Image) {
                                $info = $file->getOriginalInfo();

                                $photo = new App_Model_Photo(array(
                                    'galleryId' => 2,
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

        $dog = App_Model_Dog::fetchDogById((int) $id);

        if ($dog === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/dog/');
        }
        
        $dogExams = $dog->exams;
        $dogExamIds = array();
        if (!empty($dogExams)) {
            foreach ($dogExams as $dogExam) {
                $dogExamIds[] = $dogExam->examId;
            }
        }
        
        $exams = App_Model_Exam::all(array('active = ?' => true));
        $users = App_Model_User::all(
                        array('role = ?' => 'role_member'), array('id', 'firstname', 'lastname')
        );

        $view->set('dog', $dog)
                ->set('exams', $exams)
                ->set('dogexamids', $dogExamIds)
                ->set('users', $users);

        if (RequestMethods::post('submitEditDog')) {
            if ($this->checkToken() !== true) {
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

            $imgMain = $imgThumb = '';
            if ($dog->imgMain == '') {
                $photoNameRaw = RequestMethods::post('user') . '-' . RequestMethods::post('dogname');
                $photoName = $this->_createUrlKey($photoNameRaw);

                $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $photoName, 'dog', time() . '_')->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($fileErrors)) {
                    $errors['croppedimage'] = $fileErrors;
                }

                if (!empty($files)) {
                    foreach ($files as $i => $file) {
                        if ($file instanceof \THCFrame\Filesystem\Image) {
                            $imgMain = trim($file->getFilename(), '.');
                            $imgThumb = trim($file->getThumbname(), '.');
                            break;
                        }
                    }
                }
            } else {
                $imgMain = $dog->imgMain;
                $imgThumb = $dog->imgThumb;
            }

            if ((int) RequestMethods::post('isactive') == 1) {
                App_Model_Dog::updateAll(array('isActive = ?' => true, 'userId = ?' => (int) RequestMethods::post('user')), array('isActive' => 0));
            }

            $dog->userId = RequestMethods::post('user');
            $dog->isActive = RequestMethods::post('isactive', 0);
            $dog->dogName = RequestMethods::post('dogname');
            $dog->race = RequestMethods::post('dograce');
            $dog->dob = RequestMethods::post('dogdob');
            $dog->information = RequestMethods::post('doginfo');
            $dog->active = RequestMethods::post('active');
            $dog->imgMain = $imgMain;
            $dog->imgThumb = $imgThumb;

            if (empty($errors) && $dog->validate()) {
                $dog->save();
                
                $examsArr = (array) RequestMethods::post('chexam');

                if ($examsArr[0] != '') {
                    $deleteStatus = App_Model_DogExam::deleteAll(array('dogId = ?' => (int) $dog->getId()));
                    if ($deleteStatus != -1) {
                        foreach ($examsArr as $exam) {
                            $de = new App_Model_DogExam(array(
                                'dogId' => (int) $dog->getId(),
                                'examId' => (int) $exam
                            ));

                            $de->save();
                            Event::fire('admin.log', array('success', 'Dog id: ' . $dog->getId() . ' has exam ' . $exam));
                        }
                    } else {
                        $errors['exams'] = array('Nastala chyba při ukládání zkoušek');
                    }
                }

                if (RequestMethods::post('uploadmorephotos') == '1') {
                    $fileErrors = $fileManager->newUpload()->upload('secondfile', 'dog', time() . '_')->getUploadErrors();
                    $files = $fileManager->getUploadedFiles();

                    if (!empty($fileErrors)) {
                        $errors['secondfile'] = $fileErrors;
                    }
                    if (!empty($files)) {
                        foreach ($files as $i => $file) {
                            if ($file instanceof \THCFrame\Filesystem\Image) {
                                $info = $file->getOriginalInfo();

                                $photo = new App_Model_Photo(array(
                                    'galleryId' => 2,
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
                @unlink($dog->getUnlinkPath());
                @unlink($dog->getUnlinkThumbPath());
                
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
                @unlink($dog->getUnlinkPath());
                @unlink($dog->getUnlinkThumbPath());
                $dog->imgMain = '';
                $dog->imgThumb = '';

                if ($dog->validate()) {
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
