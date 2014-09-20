<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of Admin_Controller_Contest
 *
 * @author Tomy
 */
class Admin_Controller_Contest extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        $contests = App_Model_Contest::all();
        $view->set('contests', $contests);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddContest')) {
            if ($this->checkToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/contest/');
            }

            $contest = new App_Model_Contest(array(
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'dateStart' => RequestMethods::post('dateStart'),
                'location' => RequestMethods::post('location'),
                'organizer' => RequestMethods::post('organizer')
            ));

            if ($contest->validate()) {
                $id = $contest->save();

                Event::fire('admin.log', array('success', 'Contest Id: ' . $id));
                $view->successMessage('Soutěž' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/contest/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $contest->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('contest', $contest);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $contest = App_Model_Contest::first(array('id = ?' => (int) $id));

        if ($contest === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/contest/');
        }

        $view->set('contest', $contest);

        if (RequestMethods::post('submitEditContest')) {
            if ($this->checkToken() !== true) {
                self::redirect('/admin/contest/');
            }

            $contest->title = RequestMethods::post('title');
            $contest->description = RequestMethods::post('description');
            $contest->dateStart = RequestMethods::post('dateStart');
            $contest->location = RequestMethods::post('location');
            $contest->organizer = RequestMethods::post('organizer');

            if ($contest->validate()) {
                $contest->save();

                Event::fire('admin.log', array('success', 'Contest Id: ' . $contest->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/contest/');
            } else {
                Event::fire('admin.log', array('fail', 'Contest Id: ' . $contest->getId()));
                $view->set('errors', $contest->getErrors())
                        ->set('contest', $contest);
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
            $contest = App_Model_Contest::first(
                            array('id = ?' => (int) $id), array('id')
            );

            if (NULL === $contest) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($contest->delete()) {
                    Event::fire('admin.log', array('success', 'Contest Id: ' . $id));
                    echo 'ok';
                } else {
                    Event::fire('admin.log', array('fail', 'Contest Id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
