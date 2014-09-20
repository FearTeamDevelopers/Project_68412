<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of Admin_Controller_Link
 *
 * @author Tomy
 */
class Admin_Controller_Link extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        $links = App_Model_Link::all();
        $view->set('links', $links);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddLink')) {
            if ($this->checkToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/link/');
            }

            $link = new App_Model_Link(array(
                'title' => RequestMethods::post('title'),
                'uri' => RequestMethods::post('url'),
                'target' => RequestMethods::post('target', '_blank'),
                'rank' => RequestMethods::post('rank', 1),
            ));

            if ($link->validate()) {
                $id = $link->save();

                Event::fire('admin.log', array('success', 'Link Id: ' . $id));
                $view->successMessage('Link' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/link/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $link->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('link', $link);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $link = App_Model_Link::first(array('id = ?' => (int) $id));

        if ($link === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/link/');
        }

        $view->set('link', $link);

        if (RequestMethods::post('submitEditLink')) {
            if ($this->checkToken() !== true) {
                self::redirect('/admin/link/');
            }

            $link->title = RequestMethods::post('title');
            $link->uri = RequestMethods::post('url');
            $link->target = RequestMethods::post('target', '_blank');
            $link->rank = RequestMethods::post('rank', 1);
            $link->active = RequestMethods::post('active');

            if ($link->validate()) {
                $link->save();

                Event::fire('admin.log', array('success', 'Link Id: ' . $link->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/link/');
            } else {
                Event::fire('admin.log', array('fail', 'Link Id: ' . $link->getId()));
                $view->set('errors', $link->getErrors())
                        ->set('link', $link);
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
            $link = App_Model_Link::first(
                            array('id = ?' => (int) $id), array('id')
            );

            if (NULL === $link) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($link->delete()) {
                    Event::fire('admin.log', array('success', 'Link Id: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'Link Id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
