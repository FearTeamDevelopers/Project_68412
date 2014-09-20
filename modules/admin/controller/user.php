<?php

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of Admin_Controller_User
 *
 * @author Tomy
 */
class Admin_Controller_User extends Controller
{

    /**
     * 
     */
    public function login()
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        if (RequestMethods::post('submitLogin')) {

            $email = RequestMethods::post('email');
            $password = RequestMethods::post('password');
            $error = false;

            if (empty($email)) {
                $view->set('account_error', 'Není zadán email');
                $error = true;
            }

            if (empty($password)) {
                $view->set('account_error', 'Není zadáno heslo');
                $error = true;
            }

            if (!$error) {
                try {
                    $security = Registry::get('security');
                    $status = $security->authenticate($email, $password);

                    if ($status === true) {
                        $user = App_Model_User::first(array('id = ?' => $this->getUser()->getId()));
                        $user->lastLogin = date('Y-m-d H:i:s', time());
                        $user->save();
                        unset($user);

                        self::redirect('/admin/');
                    } else {
                        $view->set('account_error', 'Email a/nebo heslo není správně');
                    }
                } catch (\Exception $e) {
                    if (ENV == 'dev') {
                        $view->set('account_error', $e->getMessage());
                    } else {
                        $view->set('account_error', 'Email a/nebo heslo není správně');
                    }
                }
            }
        }
    }

    /**
     * 
     */
    public function logout()
    {
        $security = Registry::get('security');
        $security->logout();
        self::redirect('/admin');
    }

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $security = Registry::get('security');

        $superAdmin = $security->isGranted('role_superadmin');

        $users = App_Model_User::all(
                    array('role <> ?' => 'role_superadmin'), 
                    array('id', 'firstname', 'lastname', 'email', 'role', 'active', 'created'), 
                    array('id' => 'asc')
        );

        $view->set('users', $users)
                ->set('superadmin', $superAdmin);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $security = Registry::get('security');
        $view = $this->getActionView();
        
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());
        
        if (RequestMethods::post('submitAddUser')) {
            if($this->checkToken() !== true && 
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true){
                self::redirect('/admin/user/');
            }
            
            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Hesla se neshodují');
            }

            $email = App_Model_User::first(array('email = ?' => RequestMethods::post('email')), array('email'));

            if ($email) {
                $errors['email'] = array('Tento email se již používá');
            }

            $salt = $security->createSalt();
            $hash = $security->getSaltedHash(RequestMethods::post('password'), $salt);

            $user = new App_Model_User(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'password' => $hash,
                'salt' => $salt,
                'role' => RequestMethods::post('role', 'role_member'),
            ));

            if (empty($errors) && $user->validate()) {
                $id = $user->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Účet'.self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $user->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('user', $user);
            }
        }
    }

    /**
     * @before _secured, _member
     */
    public function updateProfile()
    {
        $view = $this->getActionView();
        $loggedUser = $this->getUser();

        $user = App_Model_User::first(
                array('active = ?' => true, 'id = ?' => $loggedUser->getId()));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/user/');
        }
        $view->set('user', $user);

        if (RequestMethods::post('submitUpdateProfile')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/user/');
            }
            
            $security = Registry::get('security');

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Hesla se neshodují');
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = App_Model_User::first(
                                array('email = ?' => RequestMethods::post('email', $user->email)), 
                                array('email')
                );

                if ($email) {
                    $errors['email'] = array('Tento email je již použit');
                }
            }

            $pass = RequestMethods::post('password');

            if ($pass === null || $pass == '') {
                $salt = $user->getSalt();
                $hash = $user->getPassword();
            } else {
                $salt = $security->createSalt();
                $hash = $security->getSaltedHash($pass, $salt);
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->password = $hash;
            $user->salt = $salt;
            $user->role = $user->getRole();
            $user->active = $user->getActive();

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('admin.log', array('success', 'ID: ' . $user->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $user->getId()));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     * @param type $id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $security = Registry::get('security');

        $user = App_Model_User::first(array('id = ?' => (int)$id));

        if (NULL === $user) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/user/');
        } elseif ($user->role == 'role_superadmin' && $this->getUser()->getRole() != 'role_superadmin') {
            $view->errorMessage(self::ERROR_MESSAGE_4);
            self::redirect('/admin/user/');
        }

        $view->set('user', $user);

        if (RequestMethods::post('submitEditUser')) {
            if($this->checkToken() !== true){
                self::redirect('/admin/user/');
            }
            
            $errors = array();

            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Hesla se neshodují');
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = App_Model_User::first(
                                array('email = ?' => RequestMethods::post('email', $user->email)), 
                                array('email')
                );

                if ($email) {
                    $errors['email'] = array('Tento email je již použit');
                }
            }

            $pass = RequestMethods::post('password');

            if ($pass === null || $pass == '') {
                $salt = $user->getSalt();
                $hash = $user->getPassword();
            } else {
                $salt = $security->createSalt();
                $hash = $security->getSaltedHash($pass, $salt);
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->password = $hash;
            $user->salt = $salt;
            $user->role = RequestMethods::post('role');
            $user->active = RequestMethods::post('active');

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->set('errors', $errors + $user->getErrors());
            }
        }
    }

    /**
     * 
     * @before _secured, _superadmin
     * @param type $id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkToken()) {
            $user = App_Model_User::first(array('id = ?' => $id));

            if (NULL === $user) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($user->delete()) {
                    Event::fire('admin.log', array('success', 'ID: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'ID: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
