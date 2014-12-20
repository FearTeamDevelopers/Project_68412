<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_News extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_News::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $news = App_Model_News::all();
        $view->set('news', $news);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddNews')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/news/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), 
                    array('/aktuality/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext'));

            $news = new App_Model_News(array(
                'title' => RequestMethods::post('title'),
                'author' => RequestMethods::post('author', $this->getUser()->getWholeName()),
                'urlKey' => $urlKey,
                'shortBody' => $shortText,
                'body' => RequestMethods::post('text'),
                'expirationDate' => RequestMethods::post('expiration'),
                'rank' => RequestMethods::post('rank', 1),
                'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
                'metaDescription' => RequestMethods::post('metadescription'),
                'metaImage' => ''
            ));

            if (empty($errors) && $news->validate()) {
                $id = $news->save();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage('News' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $news->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('news', $news);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $news = App_Model_News::first(array('id = ?' => (int) $id));

        if ($news === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/news/');
        }

        $view->set('news', $news);

        if (RequestMethods::post('submitEditNews')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/news/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($news->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), 
                    array('/aktuality/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext'));

            $news->title = RequestMethods::post('title');
            $news->urlKey = $urlKey;
            $news->author = RequestMethods::post('author', $this->getUser()->getWholeName());
            $news->expirationDate = RequestMethods::post('expiration');
            $news->body = RequestMethods::post('text');
            $news->shortBody = $shortText;
            $news->rank = RequestMethods::post('rank', 1);
            $news->active = RequestMethods::post('active');
            $news->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
            $news->metaDescription = RequestMethods::post('metadescription');
            $news->metaImage = '';

            if (empty($errors) && $news->validate()) {
                $news->save();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail', 'News id: ' . $id));
                $view->set('errors', $errors + $news->getErrors());
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

        $news = App_Model_News::first(
                        array('id = ?' => (int) $id), array('id')
        );

        if (NULL === $news) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($news->delete()) {
                Event::fire('admin.log', array('success', 'News id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'News id: ' . $id));
                echo self::ERROR_MESSAGE_1;
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

        $news = App_Model_News::all(array('active = ?' => true));

        $view->set('news', $news);
    }

    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $errors = array();

        $ids = RequestMethods::post('ids');
        $action = RequestMethods::post('action');

        if (empty($ids)) {
            echo 'Nějaký řádek musí být označen';
            return;
        }

        switch ($action) {
            case 'delete':
                $news = App_Model_News::all(array(
                            'id IN ?' => $ids
                ));

                if (NULL !== $news) {
                    foreach ($news as $_news) {
                        if (!$_news->delete()) {
                            $errors[] = self::ERROR_MESSAGE_3 . ' ' . $_news->getTitle();
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('delete success', 'News ids: ' . join(',', $ids)));
                    echo self::SUCCESS_MESSAGE_6;
                } else {
                    Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'activate':
                $news = App_Model_News::all(array(
                            'id IN ?' => $ids
                ));

                if (NULL !== $news) {
                    foreach ($news as $_news) {
                        $_news->active = true;

                        if ($_news->validate()) {
                            $_news->save();
                        } else {
                            $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                    . join(', ', $_news->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('activate success', 'News ids: ' . join(',', $ids)));
                    echo self::SUCCESS_MESSAGE_4;
                } else {
                    Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'deactivate':
                $news = App_Model_News::all(array(
                            'id IN ?' => $ids
                ));

                if (NULL !== $news) {
                    foreach ($news as $_news) {
                        $_news->active = false;

                        if ($_news->validate()) {
                            $_news->save();
                        } else {
                            $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                    . join(', ', $_news->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('deactivate success', 'News ids: ' . join(',', $ids)));
                    echo self::SUCCESS_MESSAGE_5;
                } else {
                    Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            default:
                echo self::ERROR_MESSAGE_2;
                break;
        }
    }

    /**
     * @before _secured, _admin
     */
    public function load()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $page = (int) RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "nw.created='?' OR nw.expirationDate='?' "
                    . "OR nw.author LIKE '%%?%%' OR nw.title LIKE '%%?%%'";

            $query = App_Model_News::getQuery(
                            array('nw.id', 'nw.author', 'nw.title', 'nw.expirationDate',
                                'nw.active', 'nw.created'))
                    ->wheresql($whereCond, $search, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('nw.id', $dir);
                } elseif ($column == 2) {
                    $query->order('nw.title', $dir);
                } elseif ($column == 3) {
                    $query->order('nw.author', $dir);
                } elseif ($column == 4) {
                    $query->order('nw.expirationDate', $dir);
                } elseif ($column == 5) {
                    $query->order('nw.created', $dir);
                }
            } else {
                $query->order('nw.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $news = App_Model_News::initialize($query);

            $countQuery = App_Model_News::getQuery(array('nw.id'))
                    ->wheresql($whereCond, $search, $search, $search, $search);

            $newsCount = App_Model_News::initialize($countQuery);
            unset($countQuery);
            $count = count($newsCount);
            unset($newsCount);
        } else {
            $query = App_Model_News::getQuery(
                            array('nw.id', 'nw.author', 'nw.title', 'nw.expirationDate',
                                'nw.active', 'nw.created'));

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('nw.id', $dir);
                } elseif ($column == 2) {
                    $query->order('nw.title', $dir);
                } elseif ($column == 3) {
                    $query->order('nw.author', $dir);
                } elseif ($column == 4) {
                    $query->order('nw.expirationDate', $dir);
                } elseif ($column == 5) {
                    $query->order('nw.created', $dir);
                }
            } else {
                $query->order('nw.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $news = App_Model_News::initialize($query);

            $count = App_Model_News::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = array();
        if ($news !== null) {
            foreach ($news as $_news) {
                if ($_news->active) {
                    $label = "<span class='labelProduct labelProductGreen'>Aktivní</span>";
                } else {
                    $label = "<span class='labelProduct labelProductRed'>Neaktivní</span>";
                }

                $arr = array();
                $arr [] = "[ \"" . $_news->getId() . "\"";
                $arr [] = "\"" . $_news->getTitle() . "\"";
                $arr [] = "\"" . $_news->getAuthor() . "\"";
                $arr [] = "\"" . $_news->getExpirationDate() . "\"";
                $arr [] = "\"" . $_news->getCreated() . "\"";
                $arr [] = "\"" . $label . "\"";

                $tempStr = "\"<a href='/admin/news/edit/" . $_news->id . "' class='btn btn3 btn_pencil' title='Upravit'></a>";

                if ($this->isAdmin()) {
                    $tempStr .= "<a href='/admin/news/delete/" . $_news->id . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }

                $arr [] = $tempStr . "\"]";
                $returnArr[] = join(',', $arr);
            }

            $str .= join(',', $returnArr) . "]}";

            echo $str;
        } else {
            $str .= "[ \"\",\"\",\"\",\"\",\"\",\"\",\"\"]]}";

            echo $str;
        }
    }

}
