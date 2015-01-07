<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Content extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_PageContent::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @before _secured, _member
     */
    public function index()
    {
        $view = $this->getActionView();

        $content = App_Model_PageContent::all();

        $view->set('content', $content);
    }

    /**
     * @before _secured, _superadmin
     */
    public function add()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('submitAddContent')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/admin/content/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('page'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Stránka s tímto názvem již existuje');
            }

            $content = new App_Model_PageContent(array(
                'pageName' => RequestMethods::post('page'),
                'urlKey' => $urlKey,
                'body' => RequestMethods::post('text'),
                'bodyEn' => RequestMethods::post('texten'),
                'metaTitle' => RequestMethods::post('metatitle'),
                'metaDescription' => RequestMethods::post('metadescription')
            ));

            if (empty($errors) && $content->validate()) {
                $id = $content->save();

                Event::fire('admin.log', array('success', 'Content id: ' . $id));
                $view->successMessage('Obsah'.self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $content->getErrors())
                    ->set('content', $content);
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $content = App_Model_PageContent::first(array('id = ?' => (int) $id));

        if (NULL === $content) {
            $view->warningMessage('Obsah nenalezen');
            $this->_willRenderActionView = false;
            self::redirect('/admin/content/');
        }

        $view->set('content', $content);

        if (RequestMethods::post('submitEditContent')) {
            if($this->checkCSRFToken() !== true){
                self::redirect('/admin/content/');
            }
            
            $cache = Registry::get('cache');
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('page'));

            if ($content->getUrlKey() !== $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Stránka s tímto názvem již existuje');
            }

            $content->pageName = RequestMethods::post('page');
            $content->urlKey = $urlKey;
            $content->body = RequestMethods::post('text');
            $content->bodyEn = RequestMethods::post('texten');
            $content->metaTitle = RequestMethods::post('metatitle');
            $content->metaDescription = RequestMethods::post('metadescription');
            $content->active = RequestMethods::post('active');

            if (empty($errors) && $content->validate()) {
                $content->save();

                Event::fire('admin.log', array('success', 'Content id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                $cache->erase($content->getUrlKey());
                
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail', 'Content id: ' . $id));
                $view->set('errors', $content->getErrors())
                    ->set('content', $content);
            }
        }
    }
}
