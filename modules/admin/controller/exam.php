<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of Admin_Controller_Exam
 *
 * @author Tomy
 */
class Admin_Controller_Exam extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        $exams = App_Model_Exam::all();
        $view->set('exams', $exams);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddExam')) {
            if ($this->checkToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/exam/');
            }

            $exam = new App_Model_Exam(array(
                'title' => RequestMethods::post('title'),
                'description' => RequestMethods::post('description'),
                'shortcut' => RequestMethods::post('shortcut'),
                'rank' => RequestMethods::post('rank', 1)
            ));

            if ($exam->validate()) {
                $id = $exam->save();

                Event::fire('admin.log', array('success', 'Exam Id: ' . $id));
                $view->successMessage('Zkouška' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/exam/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $exam->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('exam', $exam);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $exam = App_Model_Exam::first(array('id = ?' => (int) $id));

        if ($exam === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/exam/');
        }

        $view->set('exam', $exam);

        if (RequestMethods::post('submitEditExam')) {
            if ($this->checkToken() !== true) {
                self::redirect('/admin/exam/');
            }

            $exam->title = RequestMethods::post('title');
            $exam->active = RequestMethods::post('active');
            $exam->description = RequestMethods::post('description');
            $exam->shortcut = RequestMethods::post('shortcut');
            $exam->rank = RequestMethods::post('rank', 1);

            if ($exam->validate()) {
                $exam->save();

                Event::fire('admin.log', array('success', 'Exam Id: ' . $exam->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/exam/');
            } else {
                Event::fire('admin.log', array('fail', 'Exam Id: ' . $exam->getId()));
                $view->set('errors', $exam->getErrors())
                        ->set('exam', $exam);
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
            $exam = App_Model_Exam::first(
                            array('id = ?' => (int) $id), array('id')
            );

            if (NULL === $exam) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($exam->delete()) {
                    Event::fire('admin.log', array('success', 'Exam Id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Exam Id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

}
